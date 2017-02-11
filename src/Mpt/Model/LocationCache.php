<?php

namespace Mpt\Model;

class LocationCache
{
    public $lat;
    public $lng;
    public $code;
    
    public function __construct($code, $lat, $lng)
    {
        $this->code = $code;
        $this->lng = $lng;
        $this->lat = $lat;
    }
}
