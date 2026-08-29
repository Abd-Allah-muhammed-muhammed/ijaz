<?php

namespace Modules\Classifieds\Support;

use Modules\Classifieds\Enums\FuelTypeEnum;
use Modules\Classifieds\Enums\TransmissionEnum;

/**
 * Maps stored transmission/fuel_type strings to the same {value,label,color}
 * shape used by other Classifieds enums. Legacy free-text junk (pre-enum)
 * is returned as a secondary payload so admin show never crashes.
 */
final class CarAdvisementEnumPresenter
{
    /**
     * @return array{value: string, label: string, color: string}|null
     */
    public static function transmission(mixed $value): ?array
    {
        return self::present($value, TransmissionEnum::class);
    }

    /**
     * @return array{value: string, label: string, color: string}|null
     */
    public static function fuelType(mixed $value): ?array
    {
        return self::present($value, FuelTypeEnum::class);
    }

    /**
     * @param  class-string<TransmissionEnum|FuelTypeEnum>  $enumClass
     * @return array{value: string, label: string, color: string}|null
     */
    private static function present(mixed $value, string $enumClass): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof $enumClass) {
            return [
                'value' => $value->value,
                'label' => $value->label(),
                'color' => $value->color(),
            ];
        }

        $string = (string) $value;
        $enum = $enumClass::tryFrom($string);

        if ($enum !== null) {
            return [
                'value' => $enum->value,
                'label' => $enum->label(),
                'color' => $enum->color(),
            ];
        }

        return [
            'value' => $string,
            'label' => $string,
            'color' => 'secondary',
        ];
    }
}
