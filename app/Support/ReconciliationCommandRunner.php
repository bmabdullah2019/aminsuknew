<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;

class ReconciliationCommandRunner
{
    /**
     * Execute a console command and capture exit code + buffered output.
     *
     * @return array{exit_code:int, output:string}
     */
    public function run(string $command, array $options = []): array
    {
        $exitCode = Artisan::call($command, $options);

        return [
            'exit_code' => (int) $exitCode,
            'output' => (string) Artisan::output(),
        ];
    }
}
