<?php
declare(strict_types=1);

namespace Mpt\Providers;

use Geocoder\ProviderAggregator;
use League\Geotools\Batch\BatchGeocoded;
use League\Geotools\Cache\CacheInterface;
use League\Geotools\Coordinate\Coordinate;
use League\Geotools\Geotools;

abstract class BaseProvider implements PrayerTimeProvider
{
    use DateTrait;

    private $cache;
    private $geotools;
    private $geocoder;

    public function __construct(Geotools $geotools, ProviderAggregator $geocoder)
    {
        $this->geotools = $geotools;
        $this->geocoder = $geocoder;
    }

    public function setGeotoolsCache(CacheInterface $cache)
    {
        $this->cache = $cache;
        return $this;
    }

    protected function reverseGeocode($lat, $lng): BatchGeocoded
    {
        $gc = $this->geotools->batch($this->geocoder);

        if (!is_null($this->cache)) {
            $gc->setCache($this->cache);
        }

        $results = $gc->reverse([
            new Coordinate([$lat, $lng])
        ])->parallel();

        return $results[0];
    }

    protected function isInCountry(BatchGeocoded $result, string $country): bool
    {
        $address = $result->getAddress();
        if (is_null($address)) {
            return false;
        }
        return $country == $address->getCountryCode();
    }

    public function getSupportedCodes(): array
    {
        return [];
    }
}
