<?php

namespace Tests\Feature\Admin;

use App\Support\ReconciliationHealthNotifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class ReconciliationHealthNotifierTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::store()->flush();
    }

    public function test_duplicate_failures_are_suppressed_within_dedupe_window(): void
    {
        config([
            'reconciliation.health_check.notifications.enabled' => true,
            'reconciliation.health_check.notifications.channels' => ['log'],
            'reconciliation.health_check.notifications.log_channel' => 'stack',
            'reconciliation.health_check.notifications.dedupe_seconds' => 600,
            'reconciliation.health_check.notifications.cache_store' => '',
        ]);

        Log::shouldReceive('channel')->once()->with('stack')->andReturnSelf();
        Log::shouldReceive('critical')->once()->with(
            'Reconciliation health check alert',
            Mockery::type('array')
        );

        $notifier = $this->app->make(ReconciliationHealthNotifier::class);
        $payload = $this->failurePayload();

        $notifier->notifyFailure($payload);
        $notifier->notifyFailure($payload);
    }

    public function test_duplicate_failures_are_not_suppressed_when_dedupe_is_disabled(): void
    {
        Cache::store()->flush();

        config([
            'reconciliation.health_check.notifications.enabled' => true,
            'reconciliation.health_check.notifications.channels' => ['log'],
            'reconciliation.health_check.notifications.log_channel' => 'stack',
            'reconciliation.health_check.notifications.dedupe_seconds' => 0,
            'reconciliation.health_check.notifications.cache_store' => '',
        ]);

        Log::shouldReceive('channel')->twice()->with('stack')->andReturnSelf();
        Log::shouldReceive('critical')->twice()->with(
            'Reconciliation health check alert',
            Mockery::type('array')
        );

        $notifier = $this->app->make(ReconciliationHealthNotifier::class);
        $payload = $this->failurePayload();

        $notifier->notifyFailure($payload);
        $notifier->notifyFailure($payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function failurePayload(): array
    {
        return [
            'ok' => false,
            'generated_at' => now()->toIso8601String(),
            'date' => '2026-02-24',
            'failed_checks' => [
                [
                    'check' => 'payments',
                    'command' => 'payments:reconcile',
                    'exit_code' => 1,
                    'issues_total' => 2,
                    'error' => 'issues_detected',
                ],
            ],
        ];
    }
}
