<?php

namespace App\Support;

/** Iranian mobile numbers, in one canonical form: 09xxxxxxxxx. */
class Mobile
{
    /**
     * Accepts Persian or Arabic digits, spaces and dashes, and the +98 / 0098 / 98 / 9xxxxxxxxx
     * forms. Returns null when the value is not an Iranian mobile number.
     */
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = strtr($value, ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
        $digits = preg_replace('/[\s\-().]/', '', $digits) ?? '';
        $digits = preg_replace('/^(\+98|0098|98)/', '0', $digits) ?? '';

        if (preg_match('/^9\d{9}$/', $digits)) {
            $digits = '0'.$digits;
        }

        return preg_match('/^09\d{9}$/', $digits) ? $digits : null;
    }
}
