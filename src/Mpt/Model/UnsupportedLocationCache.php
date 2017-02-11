<?php

namespace Mpt\Model;

class UnsupportedLocationCache
{
    public $lat;
    public $lng;
    public $locations;

    public function __construct($lat, $lng, array $locations)
    {
        $this->lat = $lat;
        $this->lng = $lng;
        $this->locations = $locations;
    }
}
