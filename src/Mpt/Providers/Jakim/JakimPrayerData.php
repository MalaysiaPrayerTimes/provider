<?php

namespace Mpt\Providers\Jakim;

use Mpt\Model\AbstractPrayerData;

class JakimPrayerData extends AbstractPrayerData
{
    private $month;
    private $year;
    private $code;
    private $times;
    private $place;
    private $origin;
    private $jakim;
    private $source;
    private $provider;
    private $lastModified;

    public function __construct()
    {
        $this->provider = 'jakim';
        $this->lastModified = new \DateTime();
    }

    public function getMonth()
    {
        return $this->month;
    }

    public function setMonth($month)
    {
        $this->month = $month;
        return $this;
    }

    public function getYear()
    {
        return $this->year;
    }

    /**
     * @param $year
     * @return JakimPrayerData
     */
    public function setYear($year)
    {
        $this->year = $year;
        return $this;
    }

    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param $code
     * @return JakimPrayerData
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    public function getTimes()
    {
        return $this->times;
    }

    /**
     * @param $times
     * @return JakimPrayerData
     */
    public function setTimes($times)
    {
        $this->times = $times;
        return $this;
    }

    public function getPlace()
    {
        return $this->place;
    }

    /**
     * @param $place
     * @return JakimPrayerData
     */
    public function setPlace($place)
    {
        $this->place = $place;
        return $this;
    }

    public function getProviderName()
    {
        return $this->provider;
    }

    public function getLastModified()
    {
        return $this->lastModified;
    }

    /**
     * @param \DateTime $date
     * @return JakimPrayerData
     */
    public function setLastModified(\DateTime $date)
    {
        $this->lastModified = $date;
        return $this;
    }

    public function getJakimCode()
    {
        return $this->jakim;
    }

    /**
     * @param $code
     * @return JakimPrayerData
     */
    public function setJakimCode($code)
    {
        $this->jakim = $code;
        return $this;
    }

    public function getOriginCode()
    {
        return $this->origin;
    }

    /**
     * @param $origin
     * @return JakimPrayerData
     */
    public function setOrigin($origin)
    {
        $this->origin = $origin;
        return $this;
    }

    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param $source
     * @return JakimPrayerData
     */
    public function setSource($source)
    {
        $this->source = $source;
        return $this;
    }

    public function getExtraAttributes()
    {
        return [
            'jakim_code' => $this->getJakimCode(),
            'jakim_source' => $this->getSource(),
        ];
    }
}
