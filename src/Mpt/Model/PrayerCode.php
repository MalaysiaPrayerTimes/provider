<?php

namespace Mpt\Model;

interface PrayerCode
{
    /**
     * @return string
     */
    public function getCode();

    /**
     * return string
     */
    public function getCity();

    /**
     * return string
     */
    public function getState();

    /**
     * return string
     */
    public function getCountry();

    /**
     * return string
     */
    public function getProviderName();
}
