<?php

namespace Mpt\Providers\Jakim;

use Mpt\Model\AbstractPrayerCode;

class JakimPrayerCode extends AbstractPrayerCode
{
    private $jakim;
    private $origin;
    private $duplicateOf;

    public function getJakimCode()
    {
        return $this->jakim;
    }

    /**
     * @param $jakim
     * @return $this
     */
    public function setJakimCode($jakim)
    {
        $this->jakim = $jakim;
        return $this;
    }

    public function getOriginCode()
    {
        return $this->origin;
    }

    /**
     * @param $origin
     * @return $this
     */
    public function setOriginCode($origin)
    {
        $this->origin = $origin;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDuplicate()
    {
        return !empty($this->getDuplicateOf());
    }

    /**
     * @return string
     */
    public function getDuplicateOf()
    {
        return $this->duplicateOf;
    }

    /**
     * @param $duplicateOf
     * @return $this
     */
    public function setDuplicateOf($duplicateOf)
    {
        $this->duplicateOf = $duplicateOf;
        return $this;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return 'MY';
    }

    /**
     * @return string
     */
    public function getProviderName()
    {
        return 'jakim';
    }
}
