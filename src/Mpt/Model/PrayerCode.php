<?php

namespace Mpt\Model;

interface PrayerCode
{
    /**
     * @return string
     */
    function getCode();

    /**
     * return string
     */
    function getCity();

    /**
     * return string
     */
    function getState();

    /**
     * return string
     */
    function getCountry();

    /**
     * return string
     */
    function getProviderName();
}
