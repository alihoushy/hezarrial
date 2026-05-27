<?php

namespace Tests\Unit;

use App\Services\Import\SmsParserService;
use App\Models\SmsPattern;
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

    public function test_it_uses_user_defined_sms_pattern(): void
    {
        $pattern = new SmsPattern([
            'pattern' => 'مبلغ (?<amount>[\d,]+) ریال (?<type>واریز|برداشت).*مانده (?<balance>[\d,]+).*کارت (?<card>\d{4})',
            'debit_keywords' => ['برداشت'],
            'credit_keywords' => ['واریز'],
        ]);
        $pattern->id = 10;

        $parsed = (new SmsParserService())->parse('مبلغ 1,250,000 ریال واریز شد. مانده 5,000,000 کارت 9876', $pattern);

        $this->assertTrue($parsed['matched']);
        $this->assertSame(1250000.0, $parsed['amount']);
        $this->assertSame(5000000.0, $parsed['balance']);
        $this->assertSame('income', $parsed['type']);
        $this->assertSame('9876', $parsed['card_last_four']);
    }
}
