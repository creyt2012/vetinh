<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Vortex\Aerospace\SatelliteEngine;
use DateTime;
use DateTimeZone;

class AerospaceTest extends TestCase
{
    /**
     * Test ISS (ZARYA) propagation consistency.
     */
    public function test_iss_propagation_is_consistent()
    {
        $engine = new SatelliteEngine();

        $iss = (object) [
            'name' => 'ISS (ZARYA)',
            'tle_line1' => '1 25544U 98067A   24053.51865741  .00016717  00000-0  31053-3 0  9999',
            'tle_line2' => '2 25544  51.6416 284.1804 0005708 262.3323 203.4939 15.49883584441011'
        ];

        // Specific time for deterministic results
        $time = new DateTime('2024-02-22 12:00:00', new DateTimeZone('UTC'));

        $result = $engine->propagate($iss, $time);

        $this->assertArrayHasKey('latitude', $result);
        $this->assertArrayHasKey('longitude', $result);
        $this->assertArrayHasKey('altitude', $result);
        $this->assertArrayHasKey('velocity', $result);

        // Assert reasonable ranges for LEO satellite
        $this->assertGreaterThanOrEqual(-90, $result['latitude']);
        $this->assertLessThanOrEqual(90, $result['latitude']);
        $this->assertGreaterThanOrEqual(-180, $result['longitude']);
        $this->assertLessThanOrEqual(180, $result['longitude']);

        $this->assertGreaterThan(300, $result['altitude']); // ISS is ~400km
        $this->assertLessThan(500, $result['altitude']);

        $this->assertGreaterThan(7, $result['velocity']); // ISS is ~7.66 km/s
        $this->assertLessThan(8, $result['velocity']);

        $this->assertEquals('SGP4 Core Engine', $result['source']);
    }

    /**
     * Test propagation failure with missing TLE.
     */
    public function test_propagation_fails_without_tle()
    {
        $engine = new SatelliteEngine();
        $brokenSat = (object) ['name' => 'Broken', 'tle_line1' => null, 'tle_line2' => null];

        $this->expectException(\Exception::class);
        $engine->propagate($brokenSat);
    }
}
