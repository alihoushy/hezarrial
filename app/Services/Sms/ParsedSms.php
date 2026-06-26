<?php

namespace App\Services\Sms;

class ParsedSms
{
    public function __construct(
        public readonly ?float $amount,
        public readonly ?string $currency,
        public readonly ?string $type,
        public readonly ?float $balanceAfter,
        public readonly ?string $bankName,
        public readonly ?\DateTimeInterface $occurredAt,
        public readonly ?string $trackingNumber,
        public readonly ?string $cardLastFour,
        public readonly string $confidence,
        public readonly ?int $patternId,
        public readonly string $rawMessage,
    ) {}

    public function isHighConfidence(): bool
    {
        return $this->confidence === 'high';
    }

    public function isParsed(): bool
    {
        return $this->amount !== null && $this->type !== null;
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'type' => $this->type,
            'balance_after' => $this->balanceAfter,
            'bank_name' => $this->bankName,
            'occurred_at' => $this->occurredAt?->format('Y-m-d H:i:s'),
            'tracking_number' => $this->trackingNumber,
            'card_last_four' => $this->cardLastFour,
            'confidence' => $this->confidence,
            'pattern_id' => $this->patternId,
        ];
    }
}
