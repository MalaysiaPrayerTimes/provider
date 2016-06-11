<?php

namespace Mpt\Model;

interface PrayerData
{

    /**
     * @return string
     */
    public function getCode();

    /**
     * @return array
     */
    public function getTimes();

    /**
     * @return string
     */
    public function getPlace();

    /**
     * @return string
     */
    public function getProviderName();

    /**
     * @return \DateTime
     */
    public function getLastModified();
}
