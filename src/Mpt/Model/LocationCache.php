<?php

namespace Mpt\Model;

class LocationCache
{
    public $lat;
    public $lng;
    public $code;
    
    public function __construct($code, $lng, $lat)
    {
        $this->code = $code;
        $this->lng = $lng;
        $this->lat = $lat;
    }
}
