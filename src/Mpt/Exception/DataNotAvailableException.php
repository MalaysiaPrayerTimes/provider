<?php

namespace Mpt\Exception;

class DataNotAvailableException extends \Exception
{
    /**
     * @var string[]
     */
    private $potentialLocations = [];

    public function getPotentialLocations()
    {
        return $this->potentialLocations;
    }

    public function setPotentialLocations($potentialLocations)
    {
        $this->potentialLocations = $potentialLocations;
    }
}
