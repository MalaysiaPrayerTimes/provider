<?php

namespace Mpt\Providers\Jakim;

use Mpt\Model\AbstractPrayerCode;

class JakimPrayerCode extends AbstractPrayerCode
{
    private $state;
    private $city;
    private $jakim;
    private $code;
    private $origin;
    private $duplicateOf;

    public function getState()
    {
        return $this->state;
    }

    /**
     * @param $state
     * @return JakimPrayerCode
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param $city
     * @return JakimPrayerCode
     */
    public function setCity($city)
    {
        $this->city = $city;
        return $this;
    }

    public function getJakimCode()
    {
        return $this->jakim;
    }

    /**
     * @param $jakim
     * @return JakimPrayerCode
     */
    public function setJakimCode($jakim)
    {
        $this->jakim = $jakim;
        return $this;
    }

    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param $code
     * @return JakimPrayerCode
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    public function getOriginCode()
    {
        return $this->origin;
    }

    /**
     * @param $origin
     * @return JakimPrayerCode
     */
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

    /**
     * @param $duplicateOf
     * @return JakimPrayerCode
     */
    public function setDuplicateOf($duplicateOf)
    {
        $this->duplicateOf = $duplicateOf;
        return $this;
    }

    public function getCountry()
    {
        return 'MY';
    }

    public function getProviderName()
    {
        return 'jakim';
    }
}
