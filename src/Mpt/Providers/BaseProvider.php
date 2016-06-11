<?php
declare(strict_types = 1);

namespace Mpt\Providers;

use Geocoder\ProviderAggregator;
use League\Geotools\Batch\BatchGeocoded;
use League\Geotools\Cache\CacheInterface;
use League\Geotools\Geotools;

abstract class BaseProvider implements PrayerTimeProvider
{
    use DateTrait;

    private $cache;
    private $geotools;
    private $geocoder;

    public function __construct(Geotools $geotools)
    {
        $this->geotools = $geotools;
        $this->geocoder = new ProviderAggregator();
    }

    public function setGeotoolsCache(CacheInterface $cache)
    {
        $this->cache = $cache;
        return $this;
    }

    public function registerGeocoderProviders(array $providers)
    {
        $this->geocoder->registerProviders($providers);
        return $this;
    }

    protected function reverseGeocode($lat, $lng): BatchGeocoded
    {
        $gc = $this->geotools->batch($this->geocoder);

        if (!is_null($this->cache)) {
            $gc->setCache($this->cache);
        }

        $results = $gc->reverse([
            new \League\Geotools\Coordinate\Coordinate([$lat, $lng])
        ])
            ->parallel();

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
}
