<?php
declare(strict_types=1);

namespace Mpt\Providers\Jakim;

use Geocoder\Geocoder;
use Geocoder\Model\Address;
use Geocoder\Model\AdminLevel;
use Goutte\Client;
use Mpt\Exception\ConnectException;
use Mpt\Exception\DataNotAvailableException;
use Mpt\Exception\InvalidCodeException;
use Mpt\Exception\InvalidDataException;
use Mpt\Exception\ProviderException;
use Mpt\Model\PrayerData;
use Mpt\Providers\BaseProvider;
use Symfony\Component\DomCrawler\Crawler;

class JakimProvider extends BaseProvider
{
    const DEFAULT_LOCATION_FILE = __DIR__ . '/Resources/default_locations.csv';
    const EXTRA_LOCATION_FILE = __DIR__ . '/Resources/extra_locations.csv';

    private $client;

    public function __construct(Geocoder $geocoder, Client $goutte)
    {
        parent::__construct($geocoder);
        $this->client = $goutte;
    }

    public function getName(): string
    {
        return 'jakim';
    }

    public function getCodeByCoordinates($lat, $lng, int $acc = 0): string
    {
        /** @var Address[] $results */
        $results = $this->reverseGeocode($lat, $lng);
        $potentialLocations = [];
        $code = null;

        if (empty($results)) {
            throw new DataNotAvailableException('No results returned from geocoder.');
        }

        foreach ($results as $address) {
            if (!$this->isInCountry($address, 'MY')) {
                $locality = $address->getLocality();

                if (!empty($locality)) {
                    $potentialLocations[] = $locality;
                }

                continue;
            }

            $locality = $address->getLocality();

            if (!empty($locality)) {
                try {
                    $potentialLocations[] = $locality;
                    $code = $this->getCodeByDistrict($locality);
                    return $code->getCode();
                } catch (InvalidCodeException $e) {
                }
            }

            /** @var AdminLevel[] $levels */
            $levels = $address->getAdminLevels();

            foreach ($levels as $level) {
                try {
                    $potentialLocations[] = $level->getName();
                    $code = $this->getCodeByDistrict($level->getName());
                    return $code->getCode();
                } catch (InvalidCodeException $e) {
                }
            }
        }

        $e = new DataNotAvailableException('No location found.');
        $e->setPotentialLocations($potentialLocations);

        throw $e;
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
            $district = $info->getCity();
            $origin = $info->getCode();
        } else {
            $ext = self::getExtraCodeInfo($code);

            if ($ext != null) {
                $district = $ext->getCity();
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
                $ch = (int) $c[0];
                $cm = (int) $c[1];

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

    public function getSupportedCodes(): array
    {
        $codes = [];
        $states = [];

        $handle1 = fopen(self::DEFAULT_LOCATION_FILE, 'r');
        $handle2 = fopen(self::EXTRA_LOCATION_FILE, 'r');

        if ($handle1) {
            while (!feof($handle1)) {
                $buffer = fgetcsv($handle1);
                $code = self::createDefaultCodeInfo($buffer[0], $buffer[1], $buffer[2], $buffer[3]);
                $codes[] = $code;
                $states[$code->getCode()] = $code->getState();
            }

            fclose($handle1);
        } else {
            throw new ProviderException('Error getting JAKIM code.');
        }

        if ($handle2) {
            while (!feof($handle2)) {
                $buffer = fgetcsv($handle2);
                $code2 = self::createExtraCodeInfo($buffer[0], $buffer[1], $buffer[2], $buffer[3]);
                $code2->setState($states[$code2->getOriginCode()]);
                $codes[] = $code2;
            }

            fclose($handle2);
        } else {
            throw new ProviderException('Error getting extended JAKIM code.');
        }

        return $codes;
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

    private function getCodeByDistrict($district): JakimPrayerCode
    {
        if (is_null($district)) {
            throw new InvalidCodeException('Empty district name was given.');
        }

        $info = self::getCodeInfo(null, $district);

        if ($info != null) {
            return $info;
        }

        $info = self::getExtraCodeInfo(null, $district);

        if ($info != null) {
            return $info;
        }

        throw new InvalidCodeException("No code found for district: $district.");
    }

    private static function getCodeInfo($code = null, $district = null)
    {
        if (empty($code) && empty($district)) {
            throw new InvalidCodeException('Empty code and district name was given.');
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
            throw new ProviderException('Error getting JAKIM code.');
        }
    }

    private static function getExtraCodeInfo($code = null, $district = null)
    {
        if (empty($code) && empty($district)) {
            throw new InvalidCodeException('Empty code and district name was given.');
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
                $dupCodeInfo = self::getCodeInfo($info->getDuplicateOf());

                if ($dupCodeInfo != null) {
                    return $dupCodeInfo;
                }

                return self::getExtraCodeInfo($info->getDuplicateOf());
            } else {
                return $info;
            }
        } else {
            throw new ProviderException('Error getting extended JAKIM code.');
        }
    }

    private static function createDefaultCodeInfo($state, $district, $jakim, $code): JakimPrayerCode
    {
        $info = new JakimPrayerCode();
        return $info->setCode($code)
            ->setState($state)
            ->setCity($district)
            ->setJakimCode($jakim);
    }

    private static function createExtraCodeInfo($district, $origin, $code, $duplicateOf): JakimPrayerCode
    {
        $info = new JakimPrayerCode();
        return $info->setCode($code)
            ->setCity($district)
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
