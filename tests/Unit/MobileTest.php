<?php

namespace Tests\Unit;

use App\Support\Mobile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MobileTest extends TestCase
{
    #[DataProvider('numbers')]
    public function test_it_normalises_iranian_mobile_numbers(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, Mobile::normalize($input));
    }

    public static function numbers(): array
    {
        return [
            'canonical' => ['09121234567', '09121234567'],
            'persian digits' => ['۰۹۱۲۱۲۳۴۵۶۷', '09121234567'],
            'arabic digits' => ['٠٩١٢١٢٣٤٥٦٧', '09121234567'],
            'plus 98' => ['+989121234567', '09121234567'],
            '0098' => ['00989121234567', '09121234567'],
            'without zero' => ['9121234567', '09121234567'],
            'with separators' => ['0912 123-4567', '09121234567'],
            'landline' => ['02112345678', null],
            'too short' => ['0912123', null],
            'letters' => ['09121abc567', null],
            'empty' => ['', null],
            'null' => [null, null],
        ];
    }
}
