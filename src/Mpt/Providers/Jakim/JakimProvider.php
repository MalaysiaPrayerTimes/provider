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
    use ParsesLocations;
    use ProvidesSupportedCodes;

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

    private function parseRawJakimData(array $times)
    {
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

                if (count($c) === 2) {
                    $ch = (int) $c[0];
                    $cm = (int) $c[1];
                } else {
                    if (strlen($time) === 4) {
                        $ch = (int) substr($time, 0, 2);
                        $cm = (int) substr($time, 2, 2);
                    } else {
                        $ch = 0;
                        $cm = 0;
                    }
                }

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

        return $parsed;
    }

    public function getByJakimCode($jakimCode): JakimPrayerData
    {
        $url = self::getJakimUrl($jakimCode, $this->getYear(), $this->getMonth());
        $times = $this->getPrayerTimes($jakimCode, $this->getYear(), $this->getMonth());

        try {
            $parsed = $this->parseRawJakimData($times);
        } catch (\Exception $e) {
            throw new InvalidDataException("Data format at e-solat ($url) may have changed.", 0, $e);
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
                throw new InvalidDataException("Invalid prayer data was found at $d.");
            }
        }
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
