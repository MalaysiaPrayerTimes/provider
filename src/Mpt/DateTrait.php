<?php

namespace Mpt;

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

    public function setYear($year)
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

    public function setMonth($month)
    {
        $this->month = $month;
        return $this;
    }
}
