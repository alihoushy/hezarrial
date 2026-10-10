<?php

namespace App\Support;

class Digits
{
    /** Replaces Latin digits with Persian ones: "1405" => "۱۴۰۵". */
    public static function persian(string $text): string
    {
        return strtr($text, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
    }
}
