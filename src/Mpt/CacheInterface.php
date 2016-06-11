<?php

namespace Mpt;

use Mpt\Model\LocationCache;
use Mpt\Model\PrayerData;

interface CacheInterface
{

    /**
     * @param $code
     * @return PrayerData|null
     */
    public function getPrayerData($code, $year, $month);

    public function cachePrayerData(PrayerData $data);

    /**
     * @param $lat
     * @param $lng
     * @param int $radius
     * @return LocationCache|null
     */
    public function getCodeByLocation($lat, $lng, $radius = 5);
    
    public function cacheLocation(LocationCache $location);
}
