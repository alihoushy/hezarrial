<?php

namespace App\Support;

class Money
{
    public static function formatRial(float|int|string|null $amount, bool $persianDigits = true): string
    {
        $formatted = number_format((float) ($amount ?? 0)).' ریال';

        return $persianDigits ? self::toPersianDigits($formatted) : $formatted;
    }

    public static function formatToman(float|int|string|null $amount, bool $persianDigits = true): string
    {
        $formatted = number_format(((float) ($amount ?? 0)) / 10).' تومان';

        return $persianDigits ? self::toPersianDigits($formatted) : $formatted;
    }

    public static function formatBoth(float|int|string|null $amount, bool $persianDigits = true): string
    {
        return self::formatRial($amount, $persianDigits).' · '.self::formatToman($amount, $persianDigits);
    }

    public static function toPersianDigits(string $value): string
    {
        return strtr($value, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
    }
}
