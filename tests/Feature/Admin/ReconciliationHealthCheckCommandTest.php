<?php

namespace Tests\Feature\Admin;

use App\Support\ReconciliationCommandRunner;
use App\Support\ReconciliationHealthNotifier;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ReconciliationHealthCheckCommandTest extends TestCase
{
    public function test_health_check_json_succeeds_when_all_checks_are_clean(): void
    {
        $fakeRunner = new FakeReconciliationCommandRunner([
            ['exit_code' => 0, 'output' => json_encode(['ok' => true, 'issues_total' => ['mismatches' => 0]])],
            ['exit_code' => 0, 'output' => json_encode(['ok' => true, 'issues_total' => 0])],
            ['exit_code' => 0, 'output' => json_encode(['ok' => true, 'issues_total' => 0])],
        ]);
        $fakeNotifier = new FakeReconciliationHealthNotifier;
        $this->app->instance(ReconciliationCommandRunner::class, $fakeRunner);
        $this->app->instance(ReconciliationHealthNotifier::class, $fakeNotifier);

        $exitCode = Artisan::call('reconciliation:health-check', [
            '--json' => true,
            '--max_issues' => 10,
            '--date' => '2026-02-24',
        ]);

        $this->assertSame(0, $exitCode);
        $payload = json_decode(trim((string) Artisan::output()), true);

        $this->assertIsArray($payload);
        $this->assertTrue((bool) ($payload['ok'] ?? false));
        $this->assertSame('2026-02-24', $payload['date'] ?? null);
        $this->assertCount(3, $payload['checks'] ?? []);

        $calls = $fakeRunner->calls;
        $this->assertCount(3, $calls);
        $this->assertSame('payments:reconcile', $calls[0]['command']);
        $this->assertSame('2026-02-24', $calls[0]['options']['--date'] ?? null);
        $this->assertSame('purchase-orders:reconcile-receipts', $calls[1]['command']);
        $this->assertSame('expenses:reconcile-procurement-links', $calls[2]['command']);
        $this->assertCount(0, $fakeNotifier->notifications);
    }

    public function test_health_check_json_fails_when_one_downstream_check_reports_issues(): void
    {
        $fakeRunner = new FakeReconciliationCommandRunner([
            ['exit_code' => 0, 'output' => json_encode(['ok' => true, 'issues_total' => 0])],
            ['exit_code' => 0, 'output' => json_encode(['ok' => false, 'issues_total' => 2])],
            ['exit_code' => 0, 'output' => json_encode(['ok' => true, 'issues_total' => 0])],
        ]);
        $fakeNotifier = new FakeReconciliationHealthNotifier;
        $this->app->instance(ReconciliationCommandRunner::class, $fakeRunner);
        $this->app->instance(ReconciliationHealthNotifier::class, $fakeNotifier);

        $exitCode = Artisan::call('reconciliation:health-check', [
            '--json' => true,
            '--max_issues' => 5,
        ]);

        $this->assertSame(1, $exitCode);
        $payload = json_decode(trim((string) Artisan::output()), true);

        $this->assertIsArray($payload);
        $this->assertFalse((bool) ($payload['ok'] ?? true));
        $this->assertNotEmpty($payload['failed_checks'] ?? []);
        $this->assertSame('purchase_receipts', $payload['failed_checks'][0]['check'] ?? null);
        $this->assertCount(1, $fakeNotifier->notifications);
        $this->assertFalse((bool) ($fakeNotifier->notifications[0]['ok'] ?? true));
    }

    public function test_health_check_json_returns_invalid_for_bad_date(): void
    {
        $fakeRunner = new FakeReconciliationCommandRunner([]);
        $fakeNotifier = new FakeReconciliationHealthNotifier;
        $this->app->instance(ReconciliationCommandRunner::class, $fakeRunner);
        $this->app->instance(ReconciliationHealthNotifier::class, $fakeNotifier);

        $exitCode = Artisan::call('reconciliation:health-check', [
            '--json' => true,
            '--date' => '2026-99-99',
        ]);

        $this->assertSame(2, $exitCode);
        $payload = json_decode(trim((string) Artisan::output()), true);

        $this->assertIsArray($payload);
        $this->assertFalse((bool) ($payload['ok'] ?? true));
        $this->assertStringContainsString('Invalid --date value', (string) ($payload['error'] ?? ''));
        $this->assertCount(0, $fakeRunner->calls);
        $this->assertCount(0, $fakeNotifier->notifications);
    }
}

class FakeReconciliationCommandRunner extends ReconciliationCommandRunner
{
    /**
     * @var array<int, array{exit_code:int, output:string}>
     */
    private array $responses;

    /**
     * @var array<int, array{command:string, options:array}>
     */
    public array $calls = [];

    /**
     * @param  array<int, array{exit_code:int, output:string}>  $responses
     */
    public function __construct(array $responses)
    {
        $this->responses = $responses;
    }

    public function run(string $command, array $options = []): array
    {
        $this->calls[] = [
            'command' => $command,
            'options' => $options,
        ];

        if (empty($this->responses)) {
            return [
                'exit_code' => 1,
                'output' => '',
            ];
        }

        return array_shift($this->responses);
    }
}

class FakeReconciliationHealthNotifier extends ReconciliationHealthNotifier
{
    /**
     * @var array<int, array<string, mixed>>
     */
    public array $notifications = [];

    public function notifyFailure(array $summaryPayload): void
    {
        $this->notifications[] = $summaryPayload;
    }
}
