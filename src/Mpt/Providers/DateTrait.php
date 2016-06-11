<?php

namespace Mpt\Providers;

trait DateTrait
{
    protected $year;
    protected $month;

    public function getYear()
    {
        if (!isset($year)) {
            $m = (int)date('m');
            $year = (int)date('Y');

            if ($this->getMonth() < $m) {
                $this->year = $year + 1;
            } else {
                $this->year = $year;
            }
        }

        return $this->year;
    }

    public function setYear(int $year): PrayerTimeProvider
    {
        $this->year = $year;
        return $this;
    }

    public function getMonth()
    {
        if (!isset($this->month)) {
            $this->month = (int)date('m');
        }

        return $this->month;
    }

    public function setMonth(int $month): PrayerTimeProvider
    {
        $this->month = $month;
        return $this;
    }
}
