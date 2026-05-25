<?php

namespace App\Console\Commands;

use App\Support\ReconciliationCommandRunner;
use App\Support\ReconciliationHealthNotifier;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconciliationHealthCheck extends Command
{
    /**
     * @var string
     */
    protected $signature = 'reconciliation:health-check
        {--date= : Reconciliation date for payments check (YYYY-MM-DD)}
        {--max_issues=50 : Max issue sample rows passed to downstream checks}
        {--fail_fast : Stop checks on first failure}
        {--json : Output machine-readable JSON summary}';

    /**
     * @var string
     */
    protected $description = 'Run reconciliation commands in JSON mode and report aggregate health';

    public function __construct(
        private ReconciliationCommandRunner $runner,
        private ReconciliationHealthNotifier $notifier
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $json = (bool) $this->option('json');
        $failFast = (bool) $this->option('fail_fast');

        $maxIssues = (int) ($this->option('max_issues') ?? 50);
        if ($maxIssues <= 0) {
            return $this->emitError('Invalid --max_issues value. Use a positive integer.', self::INVALID, $json);
        }

        $dateOption = (string) ($this->option('date') ?? '');
        $date = null;
        if ($dateOption !== '') {
            try {
                $date = Carbon::parse($dateOption)->toDateString();
            } catch (\Throwable) {
                return $this->emitError('Invalid --date value. Use YYYY-MM-DD format.', self::INVALID, $json);
            }
        }

        $checks = [
            [
                'key' => 'payments',
                'command' => 'payments:reconcile',
                'options' => array_filter([
                    '--date' => $date,
                    '--json' => true,
                    '--max_issues' => $maxIssues,
                ], static fn ($value) => $value !== null && $value !== ''),
            ],
            [
                'key' => 'purchase_receipts',
                'command' => 'purchase-orders:reconcile-receipts',
                'options' => [
                    '--json' => true,
                    '--max_issues' => $maxIssues,
                ],
            ],
            [
                'key' => 'expense_procurement',
                'command' => 'expenses:reconcile-procurement-links',
                'options' => [
                    '--json' => true,
                    '--max_issues' => $maxIssues,
                ],
            ],
        ];

        $results = [];
        $hasFailures = false;

        if (! $json) {
            $this->info('Running reconciliation health checks...');
            $this->line('fail_fast: '.($failFast ? 'yes' : 'no'));
            $this->line('max_issues: '.$maxIssues);
            if ($date !== null) {
                $this->line('date: '.$date);
            }
        }

        foreach ($checks as $check) {
            $execution = $this->runner->run($check['command'], $check['options']);
            $payload = $this->decodeJsonPayload((string) ($execution['output'] ?? ''));

            $payloadOk = is_array($payload) && (($payload['ok'] ?? false) === true);
            $exitCode = (int) ($execution['exit_code'] ?? 1);
            $isHealthy = $exitCode === 0 && $payloadOk;
            $issuesTotal = $this->resolveIssuesTotal($payload);
            $error = $this->resolvePayloadError($payload, $exitCode, $payloadOk);

            $result = [
                'check' => (string) $check['key'],
                'command' => (string) $check['command'],
                'exit_code' => $exitCode,
                'ok' => $isHealthy,
                'issues_total' => $issuesTotal,
                'error' => $error,
                'payload' => $payload,
            ];
            $results[] = $result;

            if (! $isHealthy) {
                $hasFailures = true;
                if ($failFast) {
                    break;
                }
            }
        }

        $failedChecks = array_values(array_filter($results, static fn (array $result) => ! $result['ok']));

        if (! $json) {
            $this->line('');
            $this->info('Summary');
            $this->table(
                ['check', 'exit_code', 'ok', 'issues_total', 'error'],
                array_map(static function (array $result): array {
                    return [
                        'check' => $result['check'],
                        'exit_code' => $result['exit_code'],
                        'ok' => $result['ok'] ? 'yes' : 'no',
                        'issues_total' => $result['issues_total'],
                        'error' => $result['error'],
                    ];
                }, $results)
            );
        }

        $summaryPayload = [
            'ok' => ! $hasFailures,
            'generated_at' => now()->toIso8601String(),
            'date' => $date,
            'checks' => array_map(static function (array $result): array {
                return [
                    'check' => $result['check'],
                    'command' => $result['command'],
                    'exit_code' => $result['exit_code'],
                    'ok' => $result['ok'],
                    'issues_total' => $result['issues_total'],
                    'error' => $result['error'],
                ];
            }, $results),
            'failed_checks' => array_values(array_map(static function (array $result): array {
                return [
                    'check' => $result['check'],
                    'command' => $result['command'],
                    'exit_code' => $result['exit_code'],
                    'issues_total' => $result['issues_total'],
                    'error' => $result['error'],
                ];
            }, $failedChecks)),
        ];

        if ($json) {
            $this->line((string) json_encode($summaryPayload));
        }

        if ($hasFailures) {
            Log::warning('Reconciliation health check failed', $summaryPayload);
            $this->notifier->notifyFailure($summaryPayload);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function decodeJsonPayload(string $output): ?array
    {
        $trimmed = trim($output);
        if ($trimmed === '') {
            return null;
        }

        $lines = preg_split('/\R/', $trimmed);
        if (! is_array($lines)) {
            return null;
        }

        for ($i = count($lines) - 1; $i >= 0; $i--) {
            $line = trim((string) $lines[$i]);
            if ($line === '') {
                continue;
            }

            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function resolveIssuesTotal(?array $payload): int
    {
        if (! is_array($payload)) {
            return -1;
        }

        if (is_numeric($payload['issues_total'] ?? null)) {
            return (int) $payload['issues_total'];
        }

        if (is_array($payload['issues_total'] ?? null)) {
            $total = 0;
            foreach ($payload['issues_total'] as $value) {
                if (is_numeric($value)) {
                    $total += (int) $value;
                }
            }

            return $total;
        }

        return 0;
    }

    private function resolvePayloadError(?array $payload, int $exitCode, bool $payloadOk): string
    {
        if ($payload === null) {
            return 'missing_or_invalid_json_output';
        }

        if (! empty($payload['error']) && is_string($payload['error'])) {
            return $payload['error'];
        }

        if ($exitCode !== 0) {
            return 'downstream_command_failure';
        }

        if (! $payloadOk) {
            return 'issues_detected';
        }

        return '';
    }

    private function emitError(string $message, int $code, bool $json): int
    {
        if ($json) {
            $this->line((string) json_encode([
                'ok' => false,
                'error' => $message,
            ]));
        } else {
            $this->error($message);
        }

        return $code;
    }
}
