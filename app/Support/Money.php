<?php

namespace App\Support;

use App\Domain\Finance\Money as MoneyValue;

class Money
{
    public static function fromMajor(int|float|string $amount): int
    {
        return MoneyValue::fromMajor($amount)->minor();
    }

    public static function toMajorInt(int $minor): int
    {
        return (int) round(MoneyValue::fromMinor(max(0, $minor))->major(), 0, PHP_ROUND_HALF_UP);
    }

    public static function toMajorFloat(int $minor): float
    {
        return MoneyValue::fromMinor($minor)->major();
    }

    public static function clampNonNegative(int $minor): int
    {
        return MoneyValue::fromMinor($minor)->clampNonNegative()->minor();
    }
}
