<?php

namespace Tests\Feature\Api\V1;

use App\Models\ApiKey;
use App\Models\Satellite;
use App\Models\Storm;
use App\Models\AlertRule;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiExpansionTest extends TestCase
{
    use RefreshDatabase;

    protected string $apiKey = 'test_key_expansion_2026';

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::create([ // Changed to use Tenant model directly
            'name' => 'Test Tenant',
            'domain' => 'test.starweather.com',
            'plan' => 'ENTERPRISE'
        ]);

        ApiKey::create([
            'tenant_id' => $tenant->id, // Added tenant_id
            'key' => $this->apiKey,
            'name' => 'Test Key',
            'is_active' => true // Changed 'status' to 'is_active'
        ]);

        // Seed necessary data
        $satA = Satellite::create([
            'name' => 'ISS',
            'norad_id' => '25544',
            'type' => 'SCIENCE',
            'status' => 'ACTIVE',
            'tle_line1' => '1 25544U 98067A   24047.53127814  .00015507  00000-0  27599-3 0  9990',
            'tle_line2' => '2 25544  51.6416 112.1868 0005748  42.4410  94.6225 15.49885895440231'
        ]);

        $satB = Satellite::create([
            'name' => 'STARLINK-TEST',
            'norad_id' => '99999',
            'type' => 'COMMUNICATION',
            'status' => 'ACTIVE',
            'tle_line1' => '1 99999U 24001A   24047.53127814  .00015507  00000-0  27599-3 0  9990',
            'tle_line2' => '2 99999  53.0000 110.0000 0001000  40.0000  90.0000 15.00000000000001'
        ]);

        \App\Models\Conjunction::create([
            'satellite_a_id' => $satA->id,
            'satellite_b_id' => $satB->id,
            'distance' => 1.5,
            'probability' => 0.001,
            'tca' => now()->addHours(2),
            'status' => 'ACTIVE'
        ]);

        \App\Models\RiskArea::create([
            'tenant_id' => $tenant->id,
            'name' => 'TEST_ZONE',
            'type' => 'CYCLONE_IMPACT',
            'severity' => 'HIGH',
            'is_active' => true
        ]);

        \App\Models\MissionFile::create([
            'name' => 'TEST_REPORT',
            'filename' => 'report_2026.pdf',
            'type' => 'METEOROLOGICAL_REPORT',
            'size' => 1024,
            'path' => 'reports/test.pdf'
        ]);

        Storm::create([
            'name' => 'EXPANSION_STORM',
            'status' => 'active',
            'latitude' => 10,
            'longitude' => 106,
            'max_wind_speed' => 120,
            'min_pressure' => 980,
            'last_updated_at' => now()
        ]);
        AlertRule::create([
            'name' => 'TEST_RULE',
            'parameter' => 'temperature',
            'operator' => '>',
            'threshold' => 35,
            'severity' => 'CRITICAL'
        ]);
    }

    public function test_storm_endpoints_accessible()
    {
        $response = $this->getJson('/api/v1/weather/storms', ['X-API-KEY' => $this->apiKey]);
        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'EXPANSION_STORM']);

        $stormId = Storm::first()->id;
        $response = $this->getJson("/api/v1/weather/storms/{$stormId}/vortex", ['X-API-KEY' => $this->apiKey]);
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['vortex_integrity', 'vertical_wind_shear']]);
    }

    public function test_satellite_telemetry_deep_dive()
    {
        $satId = Satellite::first()->id;
        $response = $this->getJson("/api/v1/satellites/{$satId}/telemetry", ['X-API-KEY' => $this->apiKey]);
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['telemetry' => ['latitude', 'longitude', 'velocity']]]);

        $response = $this->getJson("/api/v1/satellites/{$satId}/tle", ['X-API-KEY' => $this->apiKey]);
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['tle' => ['line1', 'line2']]]);
    }

    public function test_system_health_metrics_accessible()
    {
        $response = $this->getJson('/api/v1/health/system', ['X-API-KEY' => $this->apiKey]);
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['Database', 'Redis', 'API Gateway']]);
    }

    public function test_alert_rules_crud_accessible()
    {
        $response = $this->getJson('/api/v1/alerts/rules', ['X-API-KEY' => $this->apiKey]);
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $response = $this->postJson('/api/v1/alerts/rules', [
            'name' => 'NEW_API_RULE',
            'parameter' => 'pressure',
            'operator' => '<',
            'threshold' => 1000,
            'severity' => 'WARNING'
        ], ['X-API-KEY' => $this->apiKey]);
        $response->assertStatus(201);
    }

    public function test_batch_2_endpoints_accessible()
    {
        // Conjunctions
        $response = $this->getJson('/api/v1/satellites/conjunctions', ['X-API-KEY' => $this->apiKey]);
        $response->assertStatus(200)->assertJsonCount(1, 'data');

        // Risk Areas
        $response = $this->getJson('/api/v1/weather/risk-areas', ['X-API-KEY' => $this->apiKey]);
        $response->assertStatus(200)->assertJsonCount(1, 'data');

        // Reports
        $response = $this->getJson('/api/v1/reports', ['X-API-KEY' => $this->apiKey]);
        $response->assertStatus(200)->assertJsonCount(1, 'data');

        // Alert History
        $response = $this->getJson('/api/v1/alerts/history', ['X-API-KEY' => $this->apiKey]);
        $response->assertStatus(200);
    }
}
