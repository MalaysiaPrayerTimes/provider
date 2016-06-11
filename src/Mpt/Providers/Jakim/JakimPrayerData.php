<?php

namespace Mpt\Providers\Jakim;

use Mpt\Model\PrayerData;

class JakimPrayerData implements PrayerData
{

    private $code;
    private $times;
    private $place;
    private $origin;
    private $jakim;
    private $source;
    private $provider;

    public function __construct()
    {
        $this->provider = 'jakim';
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

    public function getTimes()
    {
        return $this->times;
    }

    public function setTimes($times)
    {
        $this->times = $times;
        return $this;
    }

    public function getPlace()
    {
        return $this->place;
    }

    public function setPlace($place)
    {
        $this->place = $place;
        return $this;
    }
    
    public function getProviderName()
    {
        return $this->provider;
    }

    public function getJakimCode()
    {
        return $this->jakim;
    }

    public function setJakimCode($code)
    {
        $this->jakim = $code;
        return $this;
    }

    public function getOriginCode()
    {
        return $this->origin;
    }

    public function setOrigin($origin)
    {
        $this->origin = $origin;
        return $this;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function setSource($source)
    {
        $this->source = $source;
        return $this;
    }
}