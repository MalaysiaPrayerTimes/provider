<?php
declare(strict_types=1);

namespace Mpt\Providers;

use Geocoder\Geocoder;
use Geocoder\Model\Address;
use Geocoder\Model\AddressCollection;

abstract class BaseProvider implements PrayerTimeProvider
{
    use DateTrait;

    private $geocoder;

    public function __construct(Geocoder $geocoder)
    {
        $this->geocoder = $geocoder;
    }

    protected function reverseGeocode($lat, $lng): AddressCollection
    {
        $results = $this->geocoder->reverse($lat, $lng);
        return $results;
    }

    protected function isInCountry(Address $address, string $country): bool
    {
        if (is_null($address)) {
            return false;
        }
        return $country == $address->getCountryCode();
    }

    public function getSupportedCodes(): array
    {
        return [];
    }
}
