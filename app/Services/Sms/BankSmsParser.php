<?php

namespace App\Services\Sms;

use App\Models\SmsPattern;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Morilog\Jalali\Jalalian;

class BankSmsParser
{
    public function parse(string $rawMessage, ?Collection $patterns = null): ParsedSms
    {
        $normalized = $this->normalizeText($rawMessage);

        if ($this->isOtpMessage($normalized)) {
            return $this->unparsedResult($rawMessage);
        }

        // Built-in bank patterns (order: most specific first)
        $result = $this->tryBluPattern($normalized, $rawMessage)
            ?? $this->tryResalatPattern($normalized, $rawMessage)
            ?? $this->tryGardeshgariPattern($normalized, $rawMessage)
            ?? $this->trySepahPattern($normalized, $rawMessage);

        if ($result !== null) {
            return $result;
        }

        // User-defined SmsPattern models
        if ($patterns && $patterns->isNotEmpty()) {
            foreach ($patterns as $pattern) {
                $result = $this->parseWithPattern($normalized, $rawMessage, $pattern);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        return $this->parseFallback($normalized, $rawMessage);
    }

    // ── OTP / non-transactional detection ───────────────────────

    private function isOtpMessage(string $text): bool
    {
        // "رمز: 904438" or "رمز:59864" (OTP code, 4-6 digits)
        if (preg_match('/رمز\s*:\s*\d{4,6}/u', $text)) {
            return true;
        }

        // "رمز پویا" (dynamic password request)
        if (Str::contains($text, 'رمز پویا')) {
            return true;
        }

        return false;
    }

    // ── Built-in bank patterns ──────────────────────────────────
    // To add a new bank: create a tryXxxPattern method and add it
    // to the chain in parse(). Each method returns null on no-match.

    private function tryBluPattern(string $normalized, string $raw): ?ParsedSms
    {
        $regex = '/بلو\n(?<direction>واریز پول|برداشت پول)\n[^\n]*?(?<amount>[\d,]+)\s*ریال[^\n]*\nموجودی:\s*(?<balance>[\d,]+)\s*ریال\n(?<time>\d{1,2}:\d{2})\n(?<date>\d{4}\.\d{2}\.\d{2})/u';

        if (preg_match($regex, $normalized, $m) !== 1) {
            return null;
        }

        $type = $m['direction'] === 'واریز پول' ? 'income' : 'expense';
        $dateStr = str_replace('.', '/', $m['date']);

        return new ParsedSms(
            amount: $this->cleanNumber($m['amount']),
            currency: 'IRR',
            type: $type,
            balanceAfter: $this->cleanNumber($m['balance']),
            bankName: 'blu',
            occurredAt: $this->parseJalaliDateTime($dateStr, $m['time']),
            trackingNumber: null,
            cardLastFour: null,
            confidence: 'high',
            patternId: null,
            rawMessage: $raw,
        );
    }

    private function tryResalatPattern(string $normalized, string $raw): ?ParsedSms
    {
        // Format: account\n(+|-)amount\nMM/DD_HH:MM\nمانده: balance[\ndescription]
        $regex = '/(?<account>\d+\.\d+\.\d+)\n(?<sign>[+-])(?<amount>[\d,]+)\s*\n(?<date>\d{1,2}\/\d{1,2})_(?<time>\d{1,2}:\d{2})\nمانده:\s*(?<balance>[\d,]+)/u';

        if (preg_match($regex, $normalized, $m) !== 1) {
            return null;
        }

        $type = $m['sign'] === '+' ? 'income' : 'expense';
        $dateStr = Jalalian::now()->getYear() . '/' . $m['date'];

        return new ParsedSms(
            amount: $this->cleanNumber($m['amount']),
            currency: null,
            type: $type,
            balanceAfter: $this->cleanNumber($m['balance']),
            bankName: 'resalat',
            occurredAt: $this->parseJalaliDateTime($dateStr, $m['time']),
            trackingNumber: null,
            cardLastFour: null,
            confidence: 'high',
            patternId: null,
            rawMessage: $raw,
        );
    }

    private function tryGardeshgariPattern(string $normalized, string $raw): ?ParsedSms
    {
        // Format: *بانک گردشگری*\ndescription\n(واریز به|برداشت از): account\nمبلغ: amount ریال\nYY/MM/DD_HH:MM\nموجودی: balance ریال
        $regex = '/\*?بانک گردشگری\*?\n[^\n]+\n(?<direction>واریز به|برداشت از):\s*(?<account>[\d.]+)\nمبلغ:\s*(?<amount>[\d,]+)\s*ریال\n(?<date>\d{2}\/\d{2}\/\d{2})_(?<time>\d{2}:\d{2})\nموجودی:\s*(?<balance>[\d,]+)\s*ریال/u';

        if (preg_match($regex, $normalized, $m) !== 1) {
            return null;
        }

        $type = Str::contains($m['direction'], 'واریز') ? 'income' : 'expense';

        // 2-digit year → 4-digit: 05 → 1405
        $parts = explode('/', $m['date']);
        $dateStr = '14' . $parts[0] . '/' . $parts[1] . '/' . $parts[2];

        return new ParsedSms(
            amount: $this->cleanNumber($m['amount']),
            currency: 'IRR',
            type: $type,
            balanceAfter: $this->cleanNumber($m['balance']),
            bankName: 'gardeshgari',
            occurredAt: $this->parseJalaliDateTime($dateStr, $m['time']),
            trackingNumber: null,
            cardLastFour: null,
            confidence: 'high',
            patternId: null,
            rawMessage: $raw,
        );
    }

    private function trySepahPattern(string $normalized, string $raw): ?ParsedSms
    {
        // Format: بانک سپه\n(واریز|برداشت):amount[ریال]\nحساب:number\nمانده:balance\nM/DD-HH:MM
        $regex = '/بانک سپه\n(?<direction>واریز|برداشت):(?<amount>[\d,]+)(?:\s*ریال)?\nحساب\s*:\s*(?<account>\d+)\nمانده:(?<balance>[\d,]+)\n(?<date>\d{1,2}\/\d{1,2})-(?<time>\d{1,2}:\d{2})/u';

        if (preg_match($regex, $normalized, $m) !== 1) {
            return null;
        }

        $type = Str::contains($m['direction'], 'واریز') ? 'income' : 'expense';
        $dateStr = Jalalian::now()->getYear() . '/' . $m['date'];

        return new ParsedSms(
            amount: $this->cleanNumber($m['amount']),
            currency: 'IRR',
            type: $type,
            balanceAfter: $this->cleanNumber($m['balance']),
            bankName: 'sepah',
            occurredAt: $this->parseJalaliDateTime($dateStr, $m['time']),
            trackingNumber: null,
            cardLastFour: null,
            confidence: 'high',
            patternId: null,
            rawMessage: $raw,
        );
    }

    // ── User-defined SmsPattern ─────────────────────────────────

    private function parseWithPattern(string $normalized, string $raw, SmsPattern $pattern): ?ParsedSms
    {
        $regex = $this->wrapRegex($pattern->pattern);
        if (@preg_match($regex, $normalized, $matches) !== 1) {
            return null;
        }

        $amount = $this->cleanNumber($matches['amount'] ?? null);
        if ($amount === null) {
            return null;
        }

        $type = $this->detectType($normalized, $pattern->debit_keywords, $pattern->credit_keywords);
        $currency = $this->detectCurrency($normalized);
        $balance = $this->cleanNumber($matches['balance'] ?? null);
        $date = $this->extractDate($matches['date'] ?? null, $matches['time'] ?? null);
        $trackingNumber = $matches['tracking'] ?? $matches['reference'] ?? null;
        $cardLastFour = $matches['card'] ?? $this->extractCardLastFour($normalized);

        return new ParsedSms(
            amount: $amount,
            currency: $currency,
            type: $type,
            balanceAfter: $balance,
            bankName: $pattern->bank_name,
            occurredAt: $date,
            trackingNumber: $trackingNumber,
            cardLastFour: $cardLastFour,
            confidence: $amount && $type ? 'high' : 'medium',
            patternId: $pattern->id,
            rawMessage: $raw,
        );
    }

    // ── Fallback generic parser ─────────────────────────────────

    private function parseFallback(string $normalized, string $raw): ParsedSms
    {
        $balance = $this->extractBalance($normalized);
        $amount = $this->extractTransactionAmount($normalized, $balance);
        $type = $this->detectType($normalized);
        $currency = $this->detectCurrency($normalized);
        $trackingNumber = $this->extractTrackingNumber($normalized);
        $cardLastFour = $this->extractCardLastFour($normalized);
        $bankName = $this->detectBankName($normalized);
        $date = $this->extractDateFromText($normalized);

        $confidence = 'low';
        if ($amount !== null && $type !== null) {
            $confidence = 'medium';
        }

        return new ParsedSms(
            amount: $amount,
            currency: $currency,
            type: $type,
            balanceAfter: $balance,
            bankName: $bankName,
            occurredAt: $date,
            trackingNumber: $trackingNumber,
            cardLastFour: $cardLastFour,
            confidence: $confidence,
            patternId: null,
            rawMessage: $raw,
        );
    }

    // ── Text normalization ──────────────────────────────────────

    public function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        $text = strtr($text, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);

        $text = strtr($text, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        $text = str_replace(['ي', 'ك', '٫'], ['ی', 'ک', '.'], $text);

        $text = str_replace('٬', ',', $text);

        return $text;
    }

    // ── Number extraction ───────────────────────────────────────

    private function cleanNumber(?string $value): ?float
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $cleaned = str_replace([',', ' ', "\xC2\xA0"], '', $value);

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    private function extractTransactionAmount(string $text, ?float $balance): ?float
    {
        if (preg_match('/مبلغ[:\s]*([\d]{1,3}(?:[, ]?\d{3})*(?:\.\d{1,2})?)/u', $text, $m)) {
            $amount = $this->cleanNumber($m[1]);
            if ($amount !== null && $amount >= 1000) {
                return $amount;
            }
        }

        preg_match_all('/(?<!\d)([\d]{1,3}(?:[, ]?\d{3})*(?:\.\d{1,2})?)(?!\d)/', $text, $matches);

        return collect($matches[1] ?? [])
            ->map(fn ($n) => $this->cleanNumber($n))
            ->filter(fn ($n) => $n !== null && $n >= 1000)
            ->when($balance !== null, fn ($c) => $c->reject(fn ($n) => $n === $balance))
            ->sort()
            ->reverse()
            ->first();
    }

    private function extractBalance(string $text): ?float
    {
        if (preg_match('/(?:مانده|موجودی|balance)[:\s]*([\d]{1,3}(?:[, ]?\d{3})*(?:\.\d{1,2})?)/u', $text, $m)) {
            return $this->cleanNumber($m[1]);
        }

        return null;
    }

    // ── Field extraction ────────────────────────────────────────

    private function extractTrackingNumber(string $text): ?string
    {
        if (preg_match('/(?:پیگیری|ارجاع|ref|tracking)[:\s]*(\d{4,20})/ui', $text, $m)) {
            return $m[1];
        }

        return null;
    }

    private function extractCardLastFour(string $text): ?string
    {
        if (preg_match('/(?:\*{1,4}|x{1,4}|\.{3,4})(\d{4})\b/', $text, $m)) {
            return $m[1];
        }

        if (preg_match('/کارت[:\s]*(\d{4})\b/u', $text, $m)) {
            return $m[1];
        }

        return null;
    }

    // ── Type detection ──────────────────────────────────────────

    private function detectType(string $text, ?array $debitKeywords = null, ?array $creditKeywords = null): ?string
    {
        $debit = $debitKeywords ?: ['برداشت', 'خرید', 'کسر', 'پرداخت', 'کارمزد', 'بدهکار', 'debit'];
        $credit = $creditKeywords ?: ['واریز', 'افزایش', 'دریافت', 'بستانکار', 'حقوق', 'credit'];

        foreach ($debit as $keyword) {
            if (Str::contains($text, $keyword, ignoreCase: true)) {
                return $this->classifyDebitType($text);
            }
        }

        foreach ($credit as $keyword) {
            if (Str::contains($text, $keyword, ignoreCase: true)) {
                return 'income';
            }
        }

        if (preg_match('/[-]\s*[\d,]+/', $text)) {
            return 'expense';
        }
        if (preg_match('/[+]\s*[\d,]+/', $text)) {
            return 'income';
        }

        return null;
    }

    private function classifyDebitType(string $text): string
    {
        if (Str::contains($text, ['خرید', 'purchase'], ignoreCase: true)) {
            return 'expense';
        }
        if (Str::contains($text, ['انتقال', 'transfer'], ignoreCase: true)) {
            return 'transfer_out';
        }
        if (Str::contains($text, ['کارمزد', 'fee'], ignoreCase: true)) {
            return 'expense';
        }

        return 'expense';
    }

    // ── Currency detection ──────────────────────────────────────

    private function detectCurrency(string $text): ?string
    {
        if (preg_match('/ریال|IRR/ui', $text)) {
            return 'IRR';
        }
        if (preg_match('/تومان|IRT/ui', $text)) {
            return 'IRT';
        }

        return null;
    }

    // ── Bank name detection (fallback) ──────────────────────────

    private function detectBankName(string $text): ?string
    {
        $banks = [
            'بلو' => 'blu',
            'ملت' => 'mellat',
            'ملی' => 'melli',
            'صادرات' => 'saderat',
            'تجارت' => 'tejarat',
            'سپه' => 'sepah',
            'پاسارگاد' => 'pasargad',
            'پارسیان' => 'parsian',
            'سامان' => 'saman',
            'اقتصاد نوین' => 'eghtesad_novin',
            'آینده' => 'ayandeh',
            'شهر' => 'shahr',
            'دی' => 'dey',
            'سینا' => 'sina',
            'رفاه' => 'refah',
            'انصار' => 'ansar',
            'کشاورزی' => 'keshavarzi',
            'مسکن' => 'maskan',
            'توسعه تعاون' => 'tosee_taavon',
            'قرض الحسنه مهر' => 'mehr',
            'رسالت' => 'resalat',
            'کارآفرین' => 'karafarin',
            'گردشگری' => 'gardeshgari',
            'ایران زمین' => 'iranzamin',
            'خاورمیانه' => 'khavarmianeh',
        ];

        foreach ($banks as $persian => $slug) {
            if (Str::contains($text, $persian, ignoreCase: true)) {
                return $slug;
            }
        }

        return null;
    }

    // ── Date parsing ────────────────────────────────────────────

    private function extractDate(?string $dateStr, ?string $timeStr): ?\DateTimeInterface
    {
        if ($dateStr === null) {
            return null;
        }

        return $this->parseJalaliDateTime($dateStr, $timeStr);
    }

    private function extractDateFromText(string $text): ?\DateTimeInterface
    {
        // Full Jalali: 1405/04/03 or 1405-04-03
        if (preg_match('/(\d{4})[\/\-.](\d{1,2})[\/\-.](\d{1,2})/', $text, $m)) {
            $year = (int) $m[1];
            if ($year >= 1300 && $year <= 1500) {
                $timeStr = null;
                if (preg_match('/(\d{1,2}):(\d{2})(?::(\d{2}))?/', $text, $tm)) {
                    $timeStr = $tm[0];
                }

                return $this->parseJalaliDateTime("{$m[1]}/{$m[2]}/{$m[3]}", $timeStr);
            }
        }

        return null;
    }

    private function parseJalaliDateTime(string $dateStr, ?string $timeStr = null): ?\DateTimeInterface
    {
        $dateStr = str_replace(['-', '.'], '/', trim($dateStr));

        if (! preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $dateStr, $m)) {
            return null;
        }

        $hour = 0;
        $minute = 0;
        $second = 0;

        if ($timeStr && preg_match('/(\d{1,2}):(\d{2})(?::(\d{2}))?/', $timeStr, $tm)) {
            $hour = (int) $tm[1];
            $minute = (int) $tm[2];
            $second = (int) ($tm[3] ?? 0);
        }

        try {
            $paddedDate = sprintf('%04d/%02d/%02d', (int) $m[1], (int) $m[2], (int) $m[3]);

            return Jalalian::fromFormat('Y/m/d', $paddedDate)
                ->toCarbon()
                ->setTime($hour, $minute, $second);
        } catch (\Exception) {
            return null;
        }
    }

    private function unparsedResult(string $raw): ParsedSms
    {
        return new ParsedSms(
            amount: null,
            currency: null,
            type: null,
            balanceAfter: null,
            bankName: null,
            occurredAt: null,
            trackingNumber: null,
            cardLastFour: null,
            confidence: 'low',
            patternId: null,
            rawMessage: $raw,
        );
    }

    private function wrapRegex(string $pattern): string
    {
        if (str_starts_with($pattern, '/') && preg_match('/\/[a-z]*$/i', $pattern)) {
            return $pattern;
        }

        return '/' . str_replace('/', '\/', $pattern) . '/u';
    }
}
