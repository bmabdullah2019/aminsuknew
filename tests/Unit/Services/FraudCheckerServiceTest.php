<?php

namespace Tests\Unit\Services;

use App\Services\FraudCheckerService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqliteDatabase;
use Tests\TestCase;

class FraudCheckerServiceTest extends TestCase
{
    use UsesInMemorySqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useInMemorySqliteDatabase();
        $this->createFraudCheckerApisTable();
        config()->set('cache.default', 'array');
        Cache::flush();
    }

    public function test_it_normalizes_bdcourier_base_url_before_api_call(): void
    {
        DB::table('fraud_checker_apis')->insert([
            'name' => 'BDCourier',
            'api_url' => 'https://api.bdcourier.com',
            'api_key' => 'secret-key',
            'query_type' => 'basic',
            'status' => 1,
            'description' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Http::fake([
            'https://api.bdcourier.com/courier-check' => Http::response([
                'status' => 'success',
                'data' => [
                    'summary' => [
                        'total_parcel' => 10,
                        'success_parcel' => 8,
                        'cancelled_parcel' => 2,
                    ],
                ],
            ], 200),
        ]);

        $result = app(FraudCheckerService::class)->checkPhone('01712345678', null, true);

        $this->assertTrue($result['success']);
        $this->assertSame(20, (int) $result['risk_score']);
        $this->assertSame('low', (string) $result['risk_level']);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.bdcourier.com/courier-check'
                && ($request->data()['phone'] ?? null) === '01712345678';
        });
    }

    public function test_normalize_configured_api_url_for_bdcourier_base_url(): void
    {
        $this->assertSame(
            'https://api.bdcourier.com/courier-check',
            FraudCheckerService::normalizeConfiguredApiUrl('https://api.bdcourier.com')
        );

        $this->assertSame(
            'https://api.bdcourier.com/courier-check',
            FraudCheckerService::normalizeConfiguredApiUrl('https://api.bdcourier.com/')
        );
    }

    private function createFraudCheckerApisTable(): void
    {
        Schema::dropIfExists('fraud_checker_apis');

        Schema::create('fraud_checker_apis', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name')->default('Fraud Checker API');
            $table->string('api_url');
            $table->string('api_key');
            $table->string('query_type')->default('basic');
            $table->boolean('status')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }
}
