<?php

namespace App\Console\Commands;

use App\Services\FraudCheckerService;
use Illuminate\Console\Command;

class TestFraudChecker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fraud:test {phone}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test fraud checker service with a phone number';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $phone = $this->argument('phone');

        $this->info("Testing fraud checker for phone: {$phone}");

        $fraudService = new FraudCheckerService;

        // Test basic check
        $this->info("\n--- Basic Risk Check ---");
        $basicResult = $fraudService->getFormattedRiskData($phone);
        $this->table(
            ['Property', 'Value'],
            [
                ['Success', $basicResult['success'] ? 'Yes' : 'No'],
                ['Risk Level', $basicResult['risk_level']],
                ['Risk Score', $basicResult['risk_score'].'%'],
                ['Badge Class', $basicResult['badge_class']],
                ['Formatted Text', $basicResult['formatted_text']],
                ['Message', $basicResult['message']],
            ]
        );

        // Test detailed analysis
        $this->info("\n--- Detailed Analysis ---");
        $detailedResult = $fraudService->getDetailedAnalysis($phone);

        if ($detailedResult['success']) {
            $this->line('✓ Analysis completed successfully');
            $this->line('  Risk Level: '.($detailedResult['basic_risk']['level'] ?? 'unknown'));
            $this->line('  Risk Score: '.($detailedResult['basic_risk']['score'] ?? 0).'%');
            $this->line('  Detailed Data: '.(! empty($detailedResult['detailed_risk']) ? 'Available' : 'Not available'));
        } else {
            $this->error('✗ Analysis failed: '.$detailedResult['message']);
        }

        // Test API connection
        $this->info("\n--- API Connection Test ---");
        $connectionTest = $fraudService->testApiConnection();

        if ($connectionTest['success']) {
            $this->info('✓ API connection successful');
        } else {
            $this->error('✗ API connection failed: '.$connectionTest['message']);
        }

        return Command::SUCCESS;
    }
}
