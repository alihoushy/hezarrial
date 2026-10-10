<?php

namespace App\Services\Sms;

use App\Models\Account;
use App\Models\IncomingSms;
use App\Models\SmsPattern;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

class SmsIngestService
{
    public function __construct(
        private readonly BankSmsParser $parser,
    ) {}

    public function ingest(User $user, string $rawMessage): IncomingSms
    {
        $idempotencyKey = $this->generateIdempotencyKey($rawMessage);

        $existing = $this->find($user, $idempotencyKey);
        if ($existing) {
            return $existing;
        }

        $patterns = SmsPattern::forUser($user)->where('is_active', true)->get();
        $parsed = $this->parser->parse($rawMessage, $patterns);

        $accountId = $this->matchAccount($user, $parsed);

        $status = $parsed->isParsed() ? 'pending' : 'unparsed';

        try {
            return IncomingSms::create([
                'user_id' => $user->id,
                'status' => $status,
                'raw_message' => $rawMessage,
                'amount' => $parsed->amount,
                'currency_detected' => $parsed->currency,
                'type' => $parsed->type,
                'balance_after' => $parsed->balanceAfter,
                'bank_name' => $parsed->bankName,
                'occurred_at' => $parsed->occurredAt,
                'tracking_number' => $parsed->trackingNumber,
                'card_last_four' => $parsed->cardLastFour,
                'account_id' => $accountId,
                'sms_pattern_id' => $parsed->patternId,
                'confidence' => $parsed->confidence,
                'idempotency_key' => $idempotencyKey,
            ]);
        } catch (UniqueConstraintViolationException) {
            // The same message arrived twice at once; the other request won.
            return $this->find($user, $idempotencyKey) ?? throw new \RuntimeException('Duplicate SMS vanished.');
        }
    }

    private function find(User $user, string $idempotencyKey): ?IncomingSms
    {
        return IncomingSms::forUser($user)->where('idempotency_key', $idempotencyKey)->first();
    }

    private function matchAccount(User $user, ParsedSms $parsed): ?int
    {
        if ($parsed->cardLastFour === null) {
            return null;
        }

        $account = Account::forUser($user)
            ->where('is_active', true)
            ->where('card_last_four', $parsed->cardLastFour)
            ->first();

        return $account?->id;
    }

    private function generateIdempotencyKey(string $rawMessage): string
    {
        return hash('sha256', mb_strtolower(trim($rawMessage)));
    }
}
