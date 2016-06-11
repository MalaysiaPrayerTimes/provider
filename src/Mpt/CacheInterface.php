<?php

namespace Mpt;

use Mpt\Model\LocationCache;
use Mpt\Model\PrayerData;

interface CacheInterface
{
    
    public function getPrayerData($code);

    public function cachePrayerData(PrayerData $data);
    
    public function getCodeByLocation($lat, $lng, $radius = 5);
    
    public function cacheLocation(LocationCache $location);
}
