<?php

namespace Mpt;

use Geocoder\Exception\NoResult;
use Mpt\Exception\DataNotAvailableException;
use Mpt\Exception\InvalidCodeException;
use Mpt\Model\LocationCache;
use Mpt\Model\PrayerData;
use Mpt\Model\UnsupportedLocationCache;
use Mpt\Providers\PrayerTimeProvider;

class Provider
{
    private $year;
    private $month;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var PrayerTimeProvider[]
     */
    private $providers = [];

    public function __construct()
    {
    }

    public function getTimesByCode($code): PrayerData
    {
        $this->throwIfNoProviders();

        $cache = $this->getCachedPrayerData($code, $this->getYear(), $this->getMonth());

        if (!is_null($cache)) {
            return $cache;
        }

        foreach ($this->providers as $provider) {
            try {
                $data = $provider->setYear($this->getYear())
                    ->setMonth($this->getMonth())
                    ->getTimesByCode($code);

                $this->cachePrayerData($data);
                return $data;
            } catch (InvalidCodeException $e) {
            }
        }

        throw new InvalidCodeException();
    }

    /**
     * @deprecated
     */
    public function getTimesByCoordinate($lat, $lng): PrayerData
    {
        return $this->getTimesByCoordinates($lat, $lng);
    }

    public function getTimesByCoordinates($lat, $lng): PrayerData
    {
        $this->throwIfNoProviders();

        $cache = $this->getCachedCodeByLocation($lat, $lng);

        if (!is_null($cache)) {
            return $this->getTimesByCode($cache->code);
        }

        if (!is_null($this->getNearestUnsupportedLocation($lat, $lng))) {
            throw new DataNotAvailableException();
        }

        $potentialLocations = [];

        foreach ($this->providers as $provider) {
            try {
                $data = $provider->setYear($this->getYear())
                    ->setMonth($this->getMonth())
                    ->getCodeByCoordinates($lat, $lng);

                $this->cacheLocation(new LocationCache($data, $lat, $lng));
                return $this->getTimesByCode($data);
            } catch (DataNotAvailableException $e) {
                $potentialLocations = array_unique(array_merge($potentialLocations, $e->getPotentialLocations()));
            } catch (NoResult $e) {
                break;
            }
        }

        $this->cacheUnsupportedLocation(new UnsupportedLocationCache($lat, $lng, $potentialLocations));

        $e = new DataNotAvailableException();
        $e->setPotentialLocations($potentialLocations);

        throw $e;
    }

    public function getSupportedCodes(): array
    {
        $this->throwIfNoProviders();

        $codes = [];

        foreach ($this->providers as $provider) {
            $codes[$provider->getName()] = $provider->getSupportedCodes();
        }

        return $codes;
    }

    private function throwIfNoProviders()
    {
        if (empty($this->providers)) {
            throw new \RuntimeException('No provider registered.');
        }
    }

    public function registerPrayerTimeProvider(PrayerTimeProvider $provider)
    {
        $this->providers[] = $provider;
        return $this;
    }

    private function getCachedPrayerData($code, $year, $month)
    {
        if (is_null($this->cache)) {
            return null;
        }

        return $this->cache->getPrayerData($code, $year, $month);
    }

    private function cachePrayerData(PrayerData $data)
    {
        if (is_null($this->cache)) {
            return;
        }

        $this->cache->cachePrayerData($data);
    }

    private function getCachedCodeByLocation($lat, $lng)
    {
        if (is_null($this->cache)) {
            return null;
        }

        return $this->cache->getCodeByLocation($lat, $lng);
    }

    private function cacheLocation(LocationCache $location)
    {
        if (is_null($this->cache)) {
            return null;
        }

        $this->cache->cacheLocation($location);
    }

    private function cacheUnsupportedLocation(UnsupportedLocationCache $location)
    {
        if (is_null($this->cache)) {
            return null;
        }

        $this->cache->cacheUnsupportedLocation($location);
    }

    private function getNearestUnsupportedLocation($lat, $lng)
    {
        if (is_null($this->cache)) {
            return null;
        }

        return $this->cache->getNearestUnsupportedLocation($lat, $lng);
    }

    public function setCache($cache)
    {
        $this->cache = $cache;
        return $this;
    }

    public function getYear()
    {
        if (!isset($this->year) || is_null($this->year)) {
            $m = (int) date('m');
            $year = (int) date('Y');

            if ($this->getMonth() < $m) {
                $this->year = $year + 1;
            } else {
                $this->year = $year;
            }
        }

        return $this->year;
    }

    public function setYear($year)
    {
        $this->year = $year;
        return $this;
    }

    public function getMonth()
    {
        if (!isset($this->month) || is_null($this->month)) {
            $this->month = (int) date('m');
        }

        return $this->month;
    }

    public function setMonth($month)
    {
        $this->month = $month;
        return $this;
    }
}
