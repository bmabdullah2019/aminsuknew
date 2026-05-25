<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ReconciliationHealthNotifier
{
    /**
     * Notify configured channels when reconciliation health check fails.
     */
    public function notifyFailure(array $summaryPayload): void
    {
        if (! config('reconciliation.health_check.notifications.enabled', false)) {
            return;
        }

        if ($this->isDuplicateFailure($summaryPayload)) {
            return;
        }

        $channels = config('reconciliation.health_check.notifications.channels', ['log']);
        if (! is_array($channels) || empty($channels)) {
            return;
        }

        $message = $this->buildAlertMessage($summaryPayload);

        foreach ($channels as $channel) {
            $channelName = strtolower(trim((string) $channel));
            if ($channelName === '') {
                continue;
            }

            try {
                if ($channelName === 'log') {
                    $this->notifyLog($summaryPayload);

                    continue;
                }

                if ($channelName === 'mail') {
                    $this->notifyMail($message);

                    continue;
                }

                if ($channelName === 'slack') {
                    $this->notifySlack($message, $summaryPayload);

                    continue;
                }

                Log::warning('Unknown reconciliation notification channel skipped', [
                    'channel' => $channelName,
                ]);
            } catch (\Throwable $exception) {
                Log::error('Reconciliation health notification failed', [
                    'channel' => $channelName,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    private function notifyLog(array $summaryPayload): void
    {
        $channel = (string) config(
            'reconciliation.health_check.notifications.log_channel',
            (string) config('logging.default', 'stack')
        );

        Log::channel($channel)->critical('Reconciliation health check alert', $summaryPayload);
    }

    private function notifyMail(string $message): void
    {
        $recipients = $this->resolveMailRecipients();
        if ($recipients === []) {
            return;
        }

        $subject = sprintf(
            '[%s][%s] Reconciliation health check failed',
            (string) config('app.name', 'Laravel'),
            (string) config('app.env', 'production')
        );

        Mail::raw($message, function ($mail) use ($recipients, $subject): void {
            $mail->to($recipients)->subject($subject);
        });
    }

    private function notifySlack(string $message, array $summaryPayload): void
    {
        $webhookUrl = trim((string) config('reconciliation.health_check.notifications.slack_webhook_url', ''));
        if ($webhookUrl === '') {
            return;
        }

        $timeoutSeconds = (int) config('reconciliation.health_check.notifications.http_timeout_seconds', 5);
        if ($timeoutSeconds <= 0) {
            $timeoutSeconds = 5;
        }

        $response = Http::timeout($timeoutSeconds)->asJson()->post($webhookUrl, [
            'text' => $message,
            'payload' => $summaryPayload,
        ]);

        if (! $response->successful()) {
            Log::warning('Reconciliation health Slack notification failed', [
                'status' => $response->status(),
            ]);
        }
    }

    /**
     * @return array<int, string>
     */
    private function resolveMailRecipients(): array
    {
        $rawRecipients = config('reconciliation.health_check.notifications.mail_to', '');
        if (is_array($rawRecipients)) {
            $candidates = $rawRecipients;
        } else {
            $candidates = explode(',', (string) $rawRecipients);
        }

        $recipients = [];
        foreach ($candidates as $candidate) {
            $email = trim((string) $candidate);
            if ($email !== '') {
                $recipients[] = $email;
            }
        }

        return array_values(array_unique($recipients));
    }

    private function buildAlertMessage(array $summaryPayload): string
    {
        $failedChecks = [];
        foreach (($summaryPayload['failed_checks'] ?? []) as $failedCheck) {
            if (is_array($failedCheck)) {
                $failedChecks[] = (string) ($failedCheck['check'] ?? 'unknown');
            }
        }

        $generatedAt = (string) ($summaryPayload['generated_at'] ?? now()->toIso8601String());
        $date = (string) ($summaryPayload['date'] ?? '');

        return implode(PHP_EOL, [
            'Reconciliation health check failed.',
            'App: '.(string) config('app.name', 'Laravel'),
            'Env: '.(string) config('app.env', 'production'),
            'Generated at: '.$generatedAt,
            'Date: '.($date !== '' ? $date : 'n/a'),
            'Failed checks: '.(empty($failedChecks) ? 'unknown' : implode(', ', $failedChecks)),
            'URL: '.(string) config('app.url'),
        ]);
    }

    private function isDuplicateFailure(array $summaryPayload): bool
    {
        $dedupeSeconds = (int) config('reconciliation.health_check.notifications.dedupe_seconds', 1800);
        if ($dedupeSeconds <= 0) {
            return false;
        }

        try {
            $cacheStore = trim((string) config('reconciliation.health_check.notifications.cache_store', ''));
            $cache = $cacheStore !== '' ? Cache::store($cacheStore) : Cache::store();

            $cacheKeyPrefix = trim((string) config(
                'reconciliation.health_check.notifications.cache_key_prefix',
                'reconciliation:health:notify:'
            ));
            if ($cacheKeyPrefix === '') {
                $cacheKeyPrefix = 'reconciliation:health:notify:';
            }

            $cacheKey = $cacheKeyPrefix.$this->buildFailureFingerprint($summaryPayload);
            if ($cache->has($cacheKey)) {
                return true;
            }

            $cache->put($cacheKey, now()->toIso8601String(), now()->addSeconds($dedupeSeconds));

            return false;
        } catch (\Throwable $exception) {
            Log::warning('Reconciliation health notification dedupe unavailable', [
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function buildFailureFingerprint(array $summaryPayload): string
    {
        $failedChecks = [];
        foreach (($summaryPayload['failed_checks'] ?? []) as $failedCheck) {
            if (! is_array($failedCheck)) {
                continue;
            }

            $failedChecks[] = [
                'check' => (string) ($failedCheck['check'] ?? ''),
                'command' => (string) ($failedCheck['command'] ?? ''),
                'error' => (string) ($failedCheck['error'] ?? ''),
            ];
        }

        $fingerprintData = [
            'app' => (string) config('app.name', 'Laravel'),
            'env' => (string) config('app.env', 'production'),
            'date' => (string) ($summaryPayload['date'] ?? ''),
            'failed_checks' => $failedChecks,
        ];

        return sha1((string) json_encode($fingerprintData));
    }
}
