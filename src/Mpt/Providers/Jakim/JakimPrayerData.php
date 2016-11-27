<?php

namespace Mpt\Providers\Jakim;

use Mpt\Model\AbstractPrayerData;

class JakimPrayerData extends AbstractPrayerData
{
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

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param $code
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return array
     */
    public function getTimes()
    {
        return $this->times;
    }

    /**
     * @param $times
     * @return $this
     */
    public function setTimes($times)
    {
        $this->times = $times;
        return $this;
    }

    /**
     * @return string
     */
    public function getPlace()
    {
        return $this->place;
    }

    /**
     * @param $place
     * @return $this
     */
    public function setPlace($place)
    {
        $this->place = $place;
        return $this;
    }

    /**
     * @return string
     */
    public function getProviderName()
    {
        return $this->provider;
    }

    /**
     * @return \DateTime
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }

    /**
     * @param \DateTime $date
     * @return $this
     */
    public function setLastModified(\DateTime $date)
    {
        $this->lastModified = $date;
        return $this;
    }

    /**
     * @return string
     */
    public function getJakimCode()
    {
        return $this->jakim;
    }

    /**
     * @param $code
     * @return $this
     */
    public function setJakimCode($code)
    {
        $this->jakim = $code;
        return $this;
    }

    /**
     * @return string
     */
    public function getOriginCode()
    {
        return $this->origin;
    }

    /**
     * @param $origin
     * @return $this
     */
    public function setOrigin($origin)
    {
        $this->origin = $origin;
        return $this;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param $source
     * @return $this
     */
    public function setSource($source)
    {
        $this->source = $source;
        return $this;
    }

    /**
     * @return array
     */
    public function getExtraAttributes()
    {
        return [
            'jakim_code' => $this->getJakimCode(),
            'jakim_source' => $this->getSource(),
        ];
    }
}
