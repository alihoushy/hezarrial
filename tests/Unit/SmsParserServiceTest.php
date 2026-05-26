<?php

namespace Tests\Unit;

use App\Services\Import\SmsParserService;
use PHPUnit\Framework\TestCase;

class SmsParserServiceTest extends TestCase
{
    public function test_it_extracts_persian_amount_and_debit_type(): void
    {
        $parsed = (new SmsParserService())->parse('برداشت مبلغ ۲۵۰,۰۰۰ ریال از کارت ۱۲۳۴');

        $this->assertSame(250000.0, $parsed['amount']);
        $this->assertSame('expense', $parsed['type']);
        $this->assertSame('1234', $parsed['card_last_four']);
    }
}
