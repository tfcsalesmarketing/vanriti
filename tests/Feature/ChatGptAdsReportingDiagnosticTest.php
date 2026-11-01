<?php

namespace Tests\Feature;

use App\Services\Analytics\ChatGptAdsReportingService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ChatGptAdsReportingDiagnosticTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->seed(SettingsSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * Requirement 2 & 5: Verify base URL is strictly https://api.ads.openai.com/v1
     * and neither omits nor duplicates /v1 regardless of configuration format.
     */
    public function test_base_url_normalization_ensures_strict_v1_without_duplication_or_omission(): void
    {
        $service = app(ChatGptAdsReportingService::class);

        // Base domain missing /v1
        Config::set('openai_ads.advertiser_api_endpoint', 'https://api.ads.openai.com');
        $this->assertSame('https://api.ads.openai.com/v1', $service->baseUrl());
        $this->assertSame('https://api.ads.openai.com/v1/ad_account', $service->endpoint('ad_account'));

        // Trailing slash
        Config::set('openai_ads.advertiser_api_endpoint', 'https://api.ads.openai.com/');
        $this->assertSame('https://api.ads.openai.com/v1', $service->baseUrl());

        // Standard /v1
        Config::set('openai_ads.advertiser_api_endpoint', 'https://api.ads.openai.com/v1');
        $this->assertSame('https://api.ads.openai.com/v1', $service->baseUrl());

        // Trailing slash after /v1
        Config::set('openai_ads.advertiser_api_endpoint', 'https://api.ads.openai.com/v1/');
        $this->assertSame('https://api.ads.openai.com/v1', $service->baseUrl());

        // Accidental duplicate /v1/v1
        Config::set('openai_ads.advertiser_api_endpoint', 'https://api.ads.openai.com/v1/v1');
        $this->assertSame('https://api.ads.openai.com/v1', $service->baseUrl());

        // Triplicate /v1/v1/v1/
        Config::set('openai_ads.advertiser_api_endpoint', 'https://api.ads.openai.com/v1/v1/v1/');
        $this->assertSame('https://api.ads.openai.com/v1', $service->baseUrl());

        // Empty string falling back to default
        Config::set('openai_ads.advertiser_api_endpoint', '');
        $this->assertSame('https://api.ads.openai.com/v1', $service->baseUrl());
    }

    /**
     * Requirement 3 & 4: Account verification request is GET /ad_account with Authorization: Bearer {API_KEY}.
     */
    public function test_verify_ad_account_calls_get_ad_account_with_bearer_token(): void
    {
        Config::set('openai_ads.advertiser_api_key', 'test_advertiser_key_abc123');
        Config::set('openai_ads.advertiser_api_endpoint', 'https://api.ads.openai.com/v1');

        Http::fake([
            'https://api.ads.openai.com/v1/ad_account' => Http::response([
                'id' => 'act_vanriti_001',
                'name' => 'VANRITI Official Ads Account',
                'currency' => 'INR',
                'status' => 'ACTIVE',
            ], 200),
        ]);

        $service = app(ChatGptAdsReportingService::class);
        $result = $service->verifyAdAccount();

        $this->assertTrue($result['success']);
        $this->assertSame(200, $result['status']);
        $this->assertSame('act_vanriti_001', $result['account']['id']);
        $this->assertSame('https://api.ads.openai.com/v1/ad_account', $result['endpoint']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.ads.openai.com/v1/ad_account'
                && $request->method() === 'GET'
                && $request->hasHeader('Authorization', 'Bearer test_advertiser_key_abc123')
                && $request->hasHeader('Accept', 'application/json');
        });
    }

    /**
     * Requirement 6 & 8: Verify the Insights endpoint is GET /ad_account/insights
     * and account ID is NOT incorrectly appended to the URL path.
     */
    public function test_platform_metrics_insights_endpoint_does_not_append_account_id_to_url_path(): void
    {
        Config::set('openai_ads.advertiser_api_key', 'test_advertiser_key_abc123');
        Config::set('openai_ads.advertiser_account_id', 'act_99887766');
        Config::set('openai_ads.advertiser_api_endpoint', 'https://api.ads.openai.com/v1');

        Http::fake([
            'https://api.ads.openai.com/v1/ad_account/insights*' => Http::response([
                'data' => [
                    [
                        'impressions' => 12500,
                        'clicks' => 340,
                        'spend' => 450.75,
                    ],
                ],
            ], 200),
        ]);

        $service = app(ChatGptAdsReportingService::class);
        $bounds = [
            'from' => '2026-11-01',
            'to' => '2026-11-02',
        ];

        $metrics = $service->platformMetrics($bounds);

        $this->assertTrue($metrics['available']);
        $this->assertSame(12500, $metrics['metrics']['impressions']);
        $this->assertSame(340, $metrics['metrics']['clicks']);
        $this->assertSame(450.75, $metrics['metrics']['spend']);

        // Assert request was made to /v1/ad_account/insights and NOT /v1/accounts/act_99887766/insights
        Http::assertSent(function ($request) {
            $url = $request->url();
            $parsedUrl = parse_url($url, PHP_URL_PATH);

            return $parsedUrl === '/v1/ad_account/insights'
                && !str_contains($url, '/accounts/act_99887766')
                && $request->hasHeader('Authorization', 'Bearer test_advertiser_key_abc123');
        });
    }

    /**
     * Requirement 10: Differentiate 401, 403, 404, 422, 429, 500 and network errors.
     */
    public function test_error_handling_distinguishes_401_403_404_429_and_network_failures(): void
    {
        Config::set('openai_ads.advertiser_api_key', 'test_key');
        $service = app(ChatGptAdsReportingService::class);
        $bounds = ['from' => '2026-11-01', 'to' => '2026-11-02'];

        // Test 401, 403, 404, 429 via sequence
        Http::fake([
            'https://api.ads.openai.com/v1/ad_account/insights*' => Http::sequence()
                ->push(['error' => ['message' => 'Incorrect API key provided.']], 401)
                ->push(['error' => ['message' => 'User does not have access to this resource.']], 403)
                ->push(['error' => ['message' => 'The requested endpoint was not found on this server.']], 404)
                ->push(['error' => ['message' => 'Rate limit exceeded.']], 429),
        ]);

        // 1. 401 Unauthorized
        $res401 = $service->platformMetrics($bounds);
        $this->assertFalse($res401['available']);
        $this->assertSame('auth_error', $res401['error_type']);
        $this->assertSame(401, $res401['status']);
        $this->assertStringContainsString('HTTP 401', $res401['reason']);

        // 2. 403 Forbidden
        $res403 = $service->platformMetrics($bounds);
        $this->assertFalse($res403['available']);
        $this->assertSame('permission_error', $res403['error_type']);
        $this->assertSame(403, $res403['status']);
        $this->assertStringContainsString('HTTP 403', $res403['reason']);

        // 3. 404 Not Found
        $res404 = $service->platformMetrics($bounds);
        $this->assertFalse($res404['available']);
        $this->assertSame('not_found', $res404['error_type']);
        $this->assertSame(404, $res404['status']);
        $this->assertStringContainsString('HTTP 404', $res404['reason']);
        $this->assertStringContainsString('ad_account', $res404['reason']);

        // 4. 429 Rate Limited
        $res429 = $service->platformMetrics($bounds);
        $this->assertFalse($res429['available']);
        $this->assertSame('rate_limited', $res429['error_type']);
        $this->assertSame(429, $res429['status']);
        $this->assertStringContainsString('HTTP 429', $res429['reason']);

        // 5. Network Exception
        Http::fake([
            'https://api.ads.openai.com/v1/ad_account/insights*' => fn () => throw new ConnectionException('Connection timed out after 5000 milliseconds'),
        ]);
        $resNet = $service->platformMetrics($bounds);
        $this->assertFalse($resNet['available']);
        $this->assertSame('network_error', $resNet['error_type']);
        $this->assertNull($resNet['status']);
        $this->assertStringContainsString('Could not connect to OpenAI Advertiser API', $resNet['reason']);
    }

    /**
     * Requirement 7: Secure logging of request URL, status, and response body,
     * while guaranteeing API key and credentials are NEVER logged.
     */
    public function test_api_key_is_never_logged_during_requests(): void
    {
        $canarySecret = 'CANARY_SECRET_OPENAI_KEY_999888';
        Config::set('openai_ads.advertiser_api_key', $canarySecret);

        Log::spy();

        Http::fake([
            'https://api.ads.openai.com/v1/ad_account' => Http::response([
                'id' => 'act_test',
                'name' => 'Test Account',
            ], 200),
        ]);

        $service = app(ChatGptAdsReportingService::class);
        $service->verifyAdAccount();

        // Verify info was logged
        Log::shouldHaveReceived('info')->withArgs(function ($message, $context) use ($canarySecret) {
            $this->assertSame('[openai_advertiser_api] HTTP response', $message);
            $this->assertSame('https://api.ads.openai.com/v1/ad_account', $context['url']);
            $this->assertSame(200, $context['status']);
            $this->assertSame('GET', $context['method']);

            // Guarantee canary secret is NOT anywhere in the context or message
            $serialized = json_encode($context) . ' ' . $message;
            $this->assertStringNotContainsString($canarySecret, $serialized);

            return true;
        });
    }

    /**
     * Requirement 9: Preserve dashboard report and first-party attribution
     * when OpenAI Advertiser API is unavailable or returns an error.
     */
    public function test_dashboard_report_preserves_first_party_metrics_when_api_is_unavailable(): void
    {
        Config::set('openai_ads.advertiser_api_key', 'test_key');

        // Simulate 404 or outage on the Ads platform
        Http::fake([
            'https://api.ads.openai.com/v1/ad_account/insights*' => Http::response(['error' => 'Not found'], 404),
        ]);

        $service = app(ChatGptAdsReportingService::class);
        $report = $service->report([
            'from' => '2026-11-01',
            'to' => '2026-11-02',
        ]);

        $this->assertIsArray($report);
        $this->assertArrayHasKey('platform', $report);
        $this->assertFalse($report['platform']['available']);
        $this->assertSame('not_found', $report['platform']['error_type']);

        // First-party attribution & summary structure remains completely preserved
        $this->assertArrayHasKey('summary', $report);
        $this->assertNull($report['summary']['impressions']);
        $this->assertNull($report['summary']['ad_clicks']);
        $this->assertNull($report['summary']['spend']);
        $this->assertArrayHasKey('website_visits', $report['summary']);
        $this->assertArrayHasKey('conversions', $report['summary']);
        $this->assertArrayHasKey('paid_revenue', $report['summary']);

        $this->assertArrayHasKey('behavior', $report);
        $this->assertArrayHasKey('orders', $report);
        $this->assertArrayHasKey('campaigns', $report);
        $this->assertArrayHasKey('products', $report);
        $this->assertArrayHasKey('series', $report);
    }
}
