<?php

namespace Tests\Unit;

use Tests\TestCase;
use Vortex\Meteo\WindEstimator;
use Illuminate\Support\Facades\Cache;

class MeteorologyTest extends TestCase
{
    /**
     * Test wind estimation logic based on temporal differences.
     */
    public function test_wind_estimation_detects_temporal_delta()
    {
        $estimator = new WindEstimator();

        $lat = 10.0;
        $lng = 106.0;

        // Mock initial state in cache
        Cache::shouldReceive('get')
            ->once()
            ->with('himawari_prev_stats_pos')
            ->andReturn(['cloud_coverage' => 20]);

        Cache::shouldReceive('put')
            ->once()
            ->with('himawari_prev_stats_pos', ['cloud_coverage' => 50], \Mockery::any());

        $currentStats = ['cloud_coverage' => 50];
        $result = $estimator->estimate($currentStats, $lat, $lng);

        $this->assertArrayHasKey('speed', $result);
        $this->assertArrayHasKey('direction', $result);

        // Coverage increased by 30%, so speed should be base (10) + 30*2 = 70
        $this->assertEquals(70, $result['speed']);

        // At Lat 10, it should be Easterlies (90 deg)
        // Note: The code adds random micro-turbulence rand(-15, 15), 
        // so we check a range if we don't mock rand().
        // Since it's a unit test, we should probably mock rand if possible, 
        // but for now we'll check the range.
        $this->assertGreaterThanOrEqual(75, $result['direction']);
        $this->assertLessThanOrEqual(105, $result['direction']);
    }
}
