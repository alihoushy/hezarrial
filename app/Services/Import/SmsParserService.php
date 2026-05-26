<?php

namespace App\Services\Import;

class SmsParserService
{
    public function parse(string $text): array
    {
        $normalized = strtr($text, ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٫' => '.', ',' => '']);
        preg_match_all('/\d+(?:\.\d{1,2})?/', $normalized, $matches);

        $amount = collect($matches[0] ?? [])->map(fn ($n) => (float) $n)->filter(fn ($n) => $n > 999)->max();
        $isDebit = preg_match('/برداشت|خرید|کسر|پرداخت|debit/i', $text) === 1;
        $isCredit = preg_match('/واریز|افزایش|دریافت|credit/i', $text) === 1;
        preg_match('/\b(\d{4})\b/', $normalized, $lastFour);

        return [
            'amount' => $amount,
            'type' => $isDebit ? 'expense' : ($isCredit ? 'income' : null),
            'card_last_four' => $lastFour[1] ?? null,
            'confidence' => $amount && ($isDebit || $isCredit) ? 'medium' : 'low',
        ];
    }
}
