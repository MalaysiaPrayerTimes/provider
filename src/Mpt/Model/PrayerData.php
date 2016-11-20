<?php

namespace Mpt\Model;

interface PrayerData
{
    /**
     * @return int
     */
    public function getMonth();

    /**
     * @return int
     */
    public function getYear();

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

    /**
     * @return array
     */
    public function getExtraAttributes();
}
