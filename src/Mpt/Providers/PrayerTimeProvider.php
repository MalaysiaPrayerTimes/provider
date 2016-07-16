<?php

namespace Mpt\Providers;

use Mpt\Model\PrayerData;

interface PrayerTimeProvider
{
    public function getName(): string;

    public function getCodeByCoordinates($lat, $lng, int $acc = 0): string;

    public function getTimesByCode(String $code): PrayerData;

    public function setYear(int $year): PrayerTimeProvider;

    public function setMonth(int $month): PrayerTimeProvider;
}
