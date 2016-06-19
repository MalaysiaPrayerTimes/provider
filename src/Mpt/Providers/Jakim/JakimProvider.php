<?php
declare(strict_types = 1);

namespace Mpt\Providers\Jakim;

use Geocoder\ProviderAggregator;
use Goutte\Client;
use League\Geotools\Geotools;
use Mpt\Exception\ConnectException;
use Mpt\Exception\DataNotAvailableException;
use Mpt\Exception\InvalidCodeException;
use Mpt\Exception\InvalidDataException;
use Mpt\Exception\SourceException;
use Mpt\Model\PrayerData;
use Mpt\Providers\BaseProvider;
use Symfony\Component\DomCrawler\Crawler;

class JakimProvider extends BaseProvider
{
    const DEFAULT_LOCATION_FILE = __DIR__ . '/Resources/default_locations.csv';
    const EXTRA_LOCATION_FILE = __DIR__ . '/Resources/extra_locations.csv';

    private $client;

    public function __construct(Geotools $geotools, ProviderAggregator $geocoder, Client $goutte)
    {
        parent::__construct($geotools, $geocoder);
        $this->client = $goutte;
    }

    public function getName(): string
    {
        return 'jakim';
    }

    public function getCodeByCoordinates($lat, $lng, int $acc = 0): string
    {
        $result = $this->reverseGeocode($lat, $lng);

        if (!$this->isInCountry($result, 'MY')) {
            throw new DataNotAvailableException();
        }

        $address = $result->getAddress();

        if (is_null($address)) {
            throw new DataNotAvailableException();
        }

        $locality = $address->getLocality();

        if (is_null($locality)) {
            throw new DataNotAvailableException();
        }

        $code = $this->getCodeByDistrict($locality);
        return $code->getCode();
    }

    public function getTimesByCode(string $code): PrayerData
    {
        $jakimCode = null;
        $district = null;
        $origin = null;
        $info = self::getCodeInfo($code);
        $finalCode = $code;

        if ($info != null) {
            $jakimCode = $info->getJakimCode();
            $district = $info->getDistrict();
            $origin = $info->getCode();
        } else {
            $ext = self::getExtraCodeInfo($code);

            if ($ext != null) {
                $district = $ext->getDistrict();
                $origin = $ext->getOriginCode();
                $finalCode = $ext->getCode();

                $info = self::getCodeInfo($ext->getOriginCode());

                if ($info != null) {
                    $jakimCode = $info->getJakimCode();
                }
            }
        }

        if ($jakimCode != null) {
            return $this->getByJakimCode($jakimCode)
                ->setCode($finalCode)
                ->setPlace($district)
                ->setOrigin($origin);

        } else {
            throw new InvalidCodeException();
        }
    }

    public function getByJakimCode($jakimCode): JakimPrayerData
    {
        $url = self::getJakimUrl($jakimCode, $this->getYear(), $this->getMonth());
        $times = $this->getPrayerTimes($jakimCode, $this->getYear(), $this->getMonth());

        $f = 0;
        $p = 0;
        $parsed = [];

        foreach ($times as $time) {
            $i = $f % 7;

            if ($i !== 0) {
                $s = ':';

                if (strpos($time, $s) === false) {
                    $s = ';';
                }

                if (strpos($time, $s) === false) {
                    $s = '.';
                }

                $c = explode($s, $time);
                $ch = (int)$c[0];
                $cm = (int)$c[1];

                if ($i > 2) {
                    if ($i === 3) {
                        if ($ch < 11) {
                            $ch += 12;
                        }
                    } else {
                        if ($ch < 12) {
                            $ch += 12;
                        }
                    }
                } else {
                    if ($ch >= 12) {
                        $ch -= 12;
                    }
                }

                $t = mktime($ch, $cm, 0, $this->getMonth(), $p + 1, $this->getYear());
                $parsed[$p][] = $t;

                if ($i === 6) {
                    $p++;
                }
            }

            $f++;
        }

        self::throwIfInvalid($parsed);

        $jpd = new JakimPrayerData();
        return $jpd->setTimes($parsed)
            ->setMonth($this->getMonth())
            ->setYear($this->getYear())
            ->setJakimCode($jakimCode)
            ->setSource($url);
    }

    private function getPrayerTimes($jakimCode, $year, $month)
    {
        $times = $this->getPrayerTimesFromEsolat($jakimCode, $year, $month);
        return $times;
    }

    private function getPrayerTimesFromEsolat($jakimCode, $year, $month)
    {
        $url = self::getJakimUrl($jakimCode, $year, $month);

        try {
            $crawler = $this->client->request('GET', $url);
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            throw new ConnectException('Error connecting to www.e-solat.gov.my.');
        }

        $times = $crawler->filter('td[align="middle"] > font[size="2"]')
            ->each(function (Crawler $node) {
                return trim($node->text());
            });

        if (!count($times)) {
            throw new InvalidDataException("Data format at e-solat ($url) may have changed.");
        }
        return $times;
    }

    private static function throwIfInvalid($t)
    {
        for ($d = 0; $d < count($t); $d++) {
            $pt = $t[$d];
            $v = (($pt[0] < $pt[1]) && ($pt[1] < $pt[2]) && ($pt[2] < $pt[3]) && ($pt[3] < $pt[4]) && ($pt[4] < $pt[5]));
            if (!$v) {
                throw new InvalidDataException("Invalid prayer data was found: $pt");
            }
        }
    }

    private function getCodeByDistrict($district): CodeInfo
    {
        if (is_null($district)) {
            throw new InvalidCodeException();
        }

        $info = self::getCodeInfo(null, $district);

        if ($info != null) {
            return $info;
        }

        $info = self::getExtraCodeInfo(null, $district);

        if ($info != null) {
            return $info;
        }

        throw new InvalidCodeException();
    }

    private static function getCodeInfo($code = null, $district = null)
    {
        if (empty($code) && empty($district)) {
            throw new InvalidCodeException();
        }

        $handle = fopen(self::DEFAULT_LOCATION_FILE, 'r');

        if ($handle) {
            $info = null;

            while (!feof($handle)) {
                $buffer = fgetcsv($handle);
                $foundDistrict = false;

                if (!empty($buffer[1]) && !empty($district)) {
                    $foundDistrict = strtolower($buffer[1]) == strtolower($district);
                }

                if ($buffer[3] == $code || $foundDistrict) {
                    $info = self::createDefaultCodeInfo($buffer[0], $buffer[1], $buffer[2], $buffer[3]);
                    break;
                }
            }

            fclose($handle);
            return $info;
        } else {
            throw new SourceException('Error getting JAKIM code.');
        }
    }

    private static function getExtraCodeInfo($code = null, $district = null)
    {
        if (empty($code) && empty($district)) {
            throw new InvalidCodeException();
        }

        $handle = fopen(self::EXTRA_LOCATION_FILE, 'r');

        if ($handle) {
            $info = null;

            while (!feof($handle)) {
                $buffer = fgetcsv($handle);
                $foundDistrict = false;

                if (!empty($buffer[0]) && !empty($district)) {
                    $foundDistrict = strtolower($buffer[0]) == strtolower($district);
                }

                if ($buffer[2] == $code || $foundDistrict) {
                    $info = self::createExtraCodeInfo($buffer[0], $buffer[1], $buffer[2], $buffer[3]);
                    break;
                }
            }

            fclose($handle);

            if ($info != null && $info->isDuplicate()) {
                return self::getExtraCodeInfo($info->getDuplicateOf());
            } else {
                return $info;
            }
        } else {
            throw new SourceException('Error getting extended JAKIM code.');
        }
    }

    private static function createDefaultCodeInfo($state, $district, $jakim, $code): CodeInfo
    {
        $info = new CodeInfo();
        return $info->setCode($code)
            ->setState($state)
            ->setDistrict($district)
            ->setJakimCode($jakim);
    }

    private static function createExtraCodeInfo($district, $origin, $code, $duplicateOf): CodeInfo
    {
        $info = new CodeInfo();
        return $info->setCode($code)
            ->setDistrict($district)
            ->setOriginCode($origin)
            ->setDuplicateOf($duplicateOf);
    }

    private static function getJakimUrl($jakimCode, $year, $month)
    {
        return "http://www.e-solat.gov.my/web/muatturun.php?"
        . "zone=$jakimCode"
        . "&year=$year"
        . "&bulan=$month"
        . "&jenis=year&lang=my&url=http://mpt.i906.my";
    }
}

class CodeInfo
{
    private $state;
    private $district;
    private $jakim;
    private $code;
    private $origin;
    private $duplicateOf;

    public function getState()
    {
        return $this->state;
    }

    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    public function getDistrict()
    {
        return $this->district;
    }

    public function setDistrict($district)
    {
        $this->district = $district;
        return $this;
    }

    public function getJakimCode()
    {
        return $this->jakim;
    }

    public function setJakimCode($jakim)
    {
        $this->jakim = $jakim;
        return $this;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    public function getOriginCode()
    {
        return $this->origin;
    }

    public function setOriginCode($origin)
    {
        $this->origin = $origin;
        return $this;
    }

    public function isDuplicate()
    {
        return !empty($this->getDuplicateOf());
    }

    public function getDuplicateOf()
    {
        return $this->duplicateOf;
    }

    public function setDuplicateOf($duplicateOf)
    {
        $this->duplicateOf = $duplicateOf;
        return $this;
    }
}
