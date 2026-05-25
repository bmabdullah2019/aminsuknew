<?php

namespace App\Services;

use App\Models\SmsGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsSenderService
{
    public function send(string $phone, string $message, bool $unicode = false): bool
    {
        $gateway = $this->resolveGateway();
        if (! $gateway) {
            Log::warning('SMS send skipped because no gateway is configured.');

            return false;
        }

        $url = (string) $gateway['url'];
        $phone = $this->normalizeBangladeshPhone($phone);
        $type = $unicode ? 'unicode' : (string) $gateway['type'];

        try {
            $payload = [
                'api_key' => (string) $gateway['api_key'],
                'type' => $type,
                'senderid' => (string) $gateway['senderid'],
                'message' => $message,
            ];

            $phoneParameter = str_contains($url, '103.89.240.228') || str_contains($url, '/api/sendsms')
                ? 'phone'
                : 'number';
            $payload[$phoneParameter] = $phone;

            $response = Http::asForm()
                ->connectTimeout(3)
                ->timeout(10)
                ->post($url, $payload);

            if (! $response->successful()) {
                Log::warning('SMS provider returned an unsuccessful HTTP response.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            $body = trim((string) $response->body());
            if (in_array($body, ['1001', '1002', '1003', '1004', '1005', '1006', '1007'], true)) {
                Log::warning('SMS provider returned an error code.', [
                    'code' => $body,
                    'phone' => $phone,
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
    }

    public function normalizeBangladeshPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';

        if (str_starts_with($digits, '880')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '88'.$digits;
        }

        if (str_starts_with($digits, '1') && strlen($digits) === 10) {
            return '880'.$digits;
        }

        return $digits;
    }

    /**
     * @return array{url:string,api_key:string,senderid:string,type:string}|null
     */
    private function resolveGateway(): ?array
    {
        $configuredUrl = (string) config('services.sms.url');
        $configuredApiKey = (string) config('services.sms.api_key');
        $configuredSenderId = (string) config('services.sms.sender_id');

        if ($configuredUrl !== '' && $configuredApiKey !== '' && $configuredSenderId !== '') {
            return [
                'url' => $configuredUrl,
                'api_key' => $configuredApiKey,
                'senderid' => $configuredSenderId,
                'type' => (string) config('services.sms.type', 'text'),
            ];
        }

        $smsGateway = SmsGateway::where(['status' => 1, 'order' => '1'])->first()
            ?: SmsGateway::where('status', 1)->first();

        if (! $smsGateway) {
            return null;
        }

        return [
            'url' => (string) $smsGateway->url,
            'api_key' => (string) $smsGateway->api_key,
            'senderid' => (string) $smsGateway->senderid,
            'type' => 'text',
        ];
    }
}
