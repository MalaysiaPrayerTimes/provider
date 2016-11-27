<?php

namespace Mpt\Model;

abstract class AbstractPrayerData implements PrayerData
{
    protected $month;
    protected $year;

    /**
     * @return int
     */
    public function getMonth()
    {
        return $this->month;
    }

    /**
     * @param $year
     * @return $this
     */
    public function setMonth($month)
    {
        $this->month = $month;
        return $this;
    }

    /**
     * @return int
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * @param $year
     * @return $this
     */
    public function setYear($year)
    {
        $this->year = $year;
        return $this;
    }

    /**
     * @return array
     */
    public function getExtraAttributes()
    {
        return [];
    }
}
