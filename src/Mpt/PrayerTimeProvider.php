<?php

namespace Mpt;

interface PrayerTimeProvider
{

    public function getProviderName();

    public function getCodeByCoordinates($lat, $lng, $acc = 0);

    public function getTimesByCode($code);

    public function setYear($year);

    public function setMonth($month);
}
