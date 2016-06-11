<?php

namespace Mpt\Model;

interface PrayerData
{

    public function getCode();

    public function getTimes();

    public function getPlace();
    
    public function getProviderName();
}
