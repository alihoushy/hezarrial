<?php

namespace App\Services\Messaging;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * sms.ir REST API v1 (https://api.sms.ir/v1, authenticated with the X-API-KEY header).
 * Success is HTTP 200 with "status": 1 in the body.
 */
class SmsIrGateway implements SmsGateway
{
    private const BASE_URL = 'https://api.sms.ir/v1';

    public function __construct(
        private readonly ?string $apiKey,
        private readonly ?string $lineNumber,
        private readonly ?string $verifyTemplateId,
        private readonly string $verifyParameter = 'Code',
    ) {}

    public function send(string $mobile, string $text): void
    {
        if (! $this->lineNumber) {
            throw new SmsException('SMSIR_LINE_NUMBER is not configured.');
        }

        $this->post('send/bulk', [
            'lineNumber' => (int) $this->lineNumber,
            'messageText' => $text,
            'mobiles' => [$mobile],
            'sendDateTime' => null,
        ]);
    }

    public function sendVerificationCode(string $mobile, string $code): void
    {
        if ($this->verifyTemplateId) {
            $this->post('send/verify', [
                'mobile' => $mobile,
                'templateId' => (int) $this->verifyTemplateId,
                'parameters' => [['name' => $this->verifyParameter, 'value' => $code]],
            ]);

            return;
        }

        $this->send($mobile, __('کد تأیید هزار ریال: :code', ['code' => $code]));
    }

    private function post(string $path, array $payload): void
    {
        if (! $this->apiKey) {
            throw new SmsException('SMSIR_API_KEY is not configured.');
        }

        try {
            $response = $this->client()->post(self::BASE_URL.'/'.$path, $payload);
        } catch (ConnectionException $exception) {
            throw new SmsException('sms.ir is unreachable: '.$exception->getMessage(), previous: $exception);
        }

        // Never include the API key or the message text in the error: it ends up in logs.
        if (! $response->successful() || (int) $response->json('status') !== 1) {
            throw new SmsException(sprintf('sms.ir rejected the request (HTTP %d, status %s): %s', $response->status(), $response->json('status') ?? '?', $response->json('message') ?? '-'));
        }
    }

    private function client(): PendingRequest
    {
        return Http::withHeaders(['X-API-KEY' => $this->apiKey])->acceptJson()->asJson()->timeout(10)->retry(2, 300, throw: false);
    }
}
