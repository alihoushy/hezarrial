<?php

namespace App\Services\Import;

use App\Models\SmsPattern;
use Illuminate\Support\Str;

class SmsParserService
{
    public function parse(string $text, ?SmsPattern $pattern = null): array
    {
        if ($pattern) {
            $parsed = $this->parseWithPattern($text, $pattern);
            if ($parsed['matched']) {
                return $parsed;
            }
        }

        $normalized = strtr($text, ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٫' => '.', ',' => '']);
        preg_match_all('/\d+(?:\.\d{1,2})?/', $normalized, $matches);

        $amount = collect($matches[0] ?? [])->map(fn ($n) => (float) $n)->filter(fn ($n) => $n > 999)->max();
        $isDebit = preg_match('/برداشت|خرید|کسر|پرداخت|debit/i', $text) === 1;
        $isCredit = preg_match('/واریز|افزایش|دریافت|credit/i', $text) === 1;
        preg_match('/\b(\d{4})\b/', $normalized, $lastFour);

        return [
            'amount' => $amount,
            'type' => $isDebit ? 'expense' : ($isCredit ? 'income' : null),
            'balance' => null,
            'date' => null,
            'card_last_four' => $lastFour[1] ?? null,
            'confidence' => $amount && ($isDebit || $isCredit) ? 'medium' : 'low',
            'matched' => false,
        ];
    }

    private function parseWithPattern(string $text, SmsPattern $pattern): array
    {
        $regex = $this->wrapRegex($pattern->pattern);
        $matched = @preg_match($regex, $text, $matches) === 1;

        if (! $matched) {
            return ['matched' => false, 'amount' => null, 'type' => null, 'balance' => null, 'date' => null, 'card_last_four' => null, 'confidence' => 'low'];
        }

        $amount = $this->normalizeNumber($matches['amount'] ?? null);
        $balance = $this->normalizeNumber($matches['balance'] ?? null);
        $direction = $matches['type'] ?? $matches['direction'] ?? $text;
        $type = $this->detectType($direction, $pattern);

        return [
            'amount' => $amount,
            'type' => $type,
            'balance' => $balance,
            'date' => $matches['date'] ?? null,
            'card_last_four' => $matches['card'] ?? null,
            'confidence' => $amount && $type ? 'high' : 'medium',
            'matched' => true,
            'pattern_id' => $pattern->id,
        ];
    }

    private function wrapRegex(string $pattern): string
    {
        if (str_starts_with($pattern, '/') && str_ends_with($pattern, '/u')) {
            return $pattern;
        }

        return '/'.str_replace('/', '\/', $pattern).'/u';
    }

    private function normalizeNumber(?string $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtr($value, ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', ',' => '', '٬' => '', '٫' => '.']);

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function detectType(string $value, SmsPattern $pattern): ?string
    {
        $debit = $pattern->debit_keywords ?: ['برداشت', 'خرید', 'کسر', 'پرداخت', 'debit'];
        $credit = $pattern->credit_keywords ?: ['واریز', 'افزایش', 'دریافت', 'credit'];

        foreach ($debit as $keyword) {
            if (Str::contains($value, $keyword, ignoreCase: true)) {
                return 'expense';
            }
        }

        foreach ($credit as $keyword) {
            if (Str::contains($value, $keyword, ignoreCase: true)) {
                return 'income';
            }
        }

        return null;
    }
}
