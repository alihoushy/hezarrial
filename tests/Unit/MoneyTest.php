<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_it_formats_rial_toman_and_persian_digits(): void
    {
        $this->assertSame('۱۲۳,۴۵۰ ریال', Money::formatRial(123450));
        $this->assertSame('۱۲,۳۴۵ تومان', Money::formatToman(123450));
        $this->assertSame('123,450 ریال', Money::formatRial(123450, false));
    }
}
