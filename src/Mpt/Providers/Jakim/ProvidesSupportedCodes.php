<?php

namespace Mpt\Providers\Jakim;

use Mpt\Exception\ProviderException;

trait ProvidesSupportedCodes
{
    public function getSupportedCodes(): array
    {
        $codes = [];
        $states = [];

        $handle1 = fopen(self::$DEFAULT_LOCATION_FILE, 'r');
        $handle2 = fopen(self::$EXTRA_LOCATION_FILE, 'r');

        if ($handle1) {
            while (!feof($handle1)) {
                $buffer = fgetcsv($handle1);

                if ($buffer === false) {
                    continue;
                }

                $code = self::createDefaultCodeInfo($buffer[0], $buffer[1], $buffer[2], $buffer[3]);
                $codes[] = $code;
                $states[$code->getCode()] = $code->getState();
            }

            fclose($handle1);
        } else {
            throw new ProviderException('Error getting JAKIM code.');
        }

        if ($handle2) {
            while (!feof($handle2)) {
                $buffer = fgetcsv($handle2);

                if ($buffer === false) {
                    continue;
                }

                $code2 = self::createExtraCodeInfo($buffer[0], $buffer[1], $buffer[2], $buffer[3]);
                $code2->setState($states[$code2->getOriginCode()]);
                $codes[] = $code2;
            }

            fclose($handle2);
        } else {
            throw new ProviderException('Error getting extended JAKIM code.');
        }

        return $codes;
    }
}
