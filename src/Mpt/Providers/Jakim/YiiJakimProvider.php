<?php

namespace Mpt\Providers\Jakim;

use Carbon\Carbon;
use Geocoder\Geocoder;
use Geocoder\Model\Address;
use Geocoder\Model\AdminLevel;
use Http\Client\Common\HttpMethodsClient;
use Http\Client\Exception;
use Http\Client\HttpClient;
use Http\Message\RequestFactory;
use Mpt\Exception\ConnectException;
use Mpt\Exception\DataNotAvailableException;
use Mpt\Exception\InvalidCodeException;
use Mpt\Exception\InvalidDataException;
use Mpt\Providers\BaseProvider;

class YiiJakimProvider extends BaseProvider
{
    use ParsesLocations;
    use ProvidesSupportedCodes;

    /** @var HttpMethodsClient */
    private $httpClient;

    public function __construct(Geocoder $geocoder, HttpClient $httpClient, RequestFactory $requestFactory)
    {
        parent::__construct($geocoder);
        $this->httpClient = new HttpMethodsClient($httpClient, $requestFactory);
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

    public function getByJakimCode($jakimCode): JakimPrayerData
    {
        $url = self::getJakimUrl($jakimCode);
        $times = $this->downloadPrayerTimes($jakimCode, $this->getYear(), $this->getMonth());

        try {
            $parsed = $this->parseRawJakimData($times);
        } catch (\Exception $e) {
            throw new InvalidDataException("Data format at e-solat ($url) may have changed.", 0, $e);
        }

        return (new JakimPrayerData())
            ->setTimes($parsed)
            ->setMonth($this->getMonth())
            ->setYear($this->getYear())
            ->setJakimCode($jakimCode)
            ->setSource($url);
    }

    private function downloadPrayerTimes($jakimCode, $year, $month)
    {
        $start = Carbon::create($year, $month)
            ->startOfMonth();

        $end = Carbon::create($year, $month)
            ->endOfMonth();

        $params = [
            'datestart' => $start->format('Y-m-d'),
            'dateend' => $end->format('Y-m-d'),
        ];

        try {
            $response = $this->httpClient
                ->post(self::getJakimUrl($jakimCode), [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ], http_build_query($params));

            return json_decode($response->getBody()->getContents(), true);
        } catch (Exception $e) {
            throw new ConnectException('Error connecting to www.e-solat.gov.my.');
        }
    }

    private function parseRawJakimData($body)
    {
        return array_map(function ($t) {
            return $this->mapToTimes($t);
        }, $body['prayerTime']);
    }

    private function mapToTimes($jakim)
    {
        $prayers = ['fajr', 'syuruk', 'dhuhr', 'asr', 'maghrib', 'isha'];
        $mapped = [];

        foreach ($prayers as $p) {
            $rawtime = $jakim[$p];

            // Astagfirullah
            $d = explode('-', $jakim['date']);
            $time = Carbon::parse("{$this->getYear()}-{$this->getMonth()}-{$d[0]} $rawtime");

            $mapped[] = $time->timestamp;
        }

        return $mapped;
    }

    private static function getJakimUrl($jakimCode)
    {
        return "https://www.e-solat.gov.my/index.php?" .
            "r=esolatApi/takwimsolat" .
            "&period=duration" .
            "&zone=$jakimCode";
    }
}
