<?php

namespace Mpt\Providers\Jakim;

use Mpt\Exception\InvalidCodeException;
use Mpt\Exception\ProviderException;
use Mpt\Model\PrayerData;

trait ParsesLocations
{
    private static $DEFAULT_LOCATION_FILE = __DIR__ . '/Resources/default_locations.csv';
    private static $EXTRA_LOCATION_FILE = __DIR__ . '/Resources/extra_locations.csv';

    private function getCodeByDistrict($district): JakimPrayerCode
    {
        if (is_null($district)) {
            throw new InvalidCodeException('Empty district name was given.');
        }

        $info = self::getCodeInfo(null, $district);

        if ($info != null) {
            return $info;
        }

        $info = self::getExtraCodeInfo(null, $district);

        if ($info != null) {
            return $info;
        }

        throw new InvalidCodeException("No code found for district: $district.");
    }


    public function getTimesByCode(string $code): PrayerData
    {
        $jakimCode = null;
        $district = null;
        $origin = null;
        $info = self::getCodeInfo($code);
        $finalCode = $code;

        if ($info != null) {
            $jakimCode = $info->getJakimCode();
            $district = $info->getCity();
            $origin = $info->getCode();
        } else {
            $ext = self::getExtraCodeInfo($code);

            if ($ext != null) {
                $district = $ext->getCity();
                $origin = $ext->getOriginCode();
                $finalCode = $ext->getCode();

                $info = self::getCodeInfo($ext->getOriginCode());

                if ($info != null) {
                    $jakimCode = $info->getJakimCode();
                }
            }
        }

        if ($jakimCode != null) {
            return $this->getByJakimCode($jakimCode)
                ->setCode($finalCode)
                ->setPlace($district)
                ->setOrigin($origin);
        } else {
            throw new InvalidCodeException();
        }
    }

    private static function getCodeInfo($code = null, $district = null)
    {
        if (empty($code) && empty($district)) {
            throw new InvalidCodeException('Empty code and district name was given.');
        }

        $handle = fopen(self::$DEFAULT_LOCATION_FILE, 'r');

        if ($handle) {
            $info = null;

            while (!feof($handle)) {
                $buffer = fgetcsv($handle);
                $foundDistrict = false;

                if (!empty($buffer[1]) && !empty($district)) {
                    $foundDistrict = strtolower($buffer[1]) == strtolower($district);
                }

                if ($buffer[3] == $code || $foundDistrict) {
                    $info = self::createDefaultCodeInfo($buffer[0], $buffer[1], $buffer[2], $buffer[3]);
                    break;
                }
            }

            fclose($handle);
            return $info;
        } else {
            throw new ProviderException('Error getting JAKIM code.');
        }
    }

    private static function getExtraCodeInfo($code = null, $district = null)
    {
        if (empty($code) && empty($district)) {
            throw new InvalidCodeException('Empty code and district name was given.');
        }

        $handle = fopen(self::$EXTRA_LOCATION_FILE, 'r');

        if ($handle) {
            $info = null;

            while (!feof($handle)) {
                $buffer = fgetcsv($handle);
                $foundDistrict = false;

                if (!empty($buffer[0]) && !empty($district)) {
                    $foundDistrict = strtolower($buffer[0]) == strtolower($district);
                }

                if ($buffer[2] == $code || $foundDistrict) {
                    $info = self::createExtraCodeInfo($buffer[0], $buffer[1], $buffer[2], $buffer[3]);
                    break;
                }
            }

            fclose($handle);

            if ($info != null && $info->isDuplicate()) {
                $dupCodeInfo = self::getCodeInfo($info->getDuplicateOf());

                if ($dupCodeInfo != null) {
                    return $dupCodeInfo;
                }

                return self::getExtraCodeInfo($info->getDuplicateOf());
            } else {
                return $info;
            }
        } else {
            throw new ProviderException('Error getting extended JAKIM code.');
        }
    }


    private static function createDefaultCodeInfo($state, $district, $jakim, $code): JakimPrayerCode
    {
        $info = new JakimPrayerCode();
        return $info->setCode($code)
            ->setState($state)
            ->setCity($district)
            ->setJakimCode($jakim);
    }

    private static function createExtraCodeInfo($district, $origin, $code, $duplicateOf): JakimPrayerCode
    {
        $info = new JakimPrayerCode();
        return $info->setCode($code)
            ->setCity($district)
            ->setOriginCode($origin)
            ->setDuplicateOf($duplicateOf);
    }
}
