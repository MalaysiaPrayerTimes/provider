<?php

namespace Mpt;

interface PrayerTimeProvider
{

    public function getProviderName(): string;

    public function getCodeByCoordinates(float $lat, float $lng, int $acc = 0): string;

    public function getTimesByCode(String $code): PrayerData;

    public function setYear(int $year): PrayerTimeProvider;

    public function setMonth(int $month): PrayerTimeProvider;
}
