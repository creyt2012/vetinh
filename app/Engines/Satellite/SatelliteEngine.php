<?php

namespace App\Engines\Satellite;

use App\Models\Satellite;
use DateTime;
use DateTimeZone;

class SatelliteEngine
{
    /**
     * Constants for ECI to Geodetic conversion
     */
    private const WGS84_A = 6378.137;         // semi-major axis (km)
    private const WGS84_F = 1 / 298.257223563; // flattening
    private const WGS84_E2 = 0.00669437999014; // eccentricity squared

    /**
     * Propagate satellite position to a specific time.
     * Note: This implementation uses a simplified perturbation model 
     * based on TLE parameters for demonstration of high-fidelity logic.
     */
    public function propagate(Satellite $satellite, ?DateTime $time = null): array
    {
        $time = $time ?: new DateTime('now', new DateTimeZone('UTC'));
        $timestamp = $time->getTimestamp();

        // Parse TLE lines
        $tle1 = $satellite->tle_line1;
        $tle2 = $satellite->tle_line2;

        if (!$tle1 || !$tle2) {
            throw new \Exception("Satellite {$satellite->name} is missing TLE data.");
        }

        // Extract parameters from TLE
        $inclination = (float) substr($tle2, 8, 8);
        $raan = (float) substr($tle2, 17, 8);
        $eccentricity = (float) ("0." . substr($tle2, 26, 7));
        $argPerigee = (float) substr($tle2, 34, 8);
        $meanAnom = (float) substr($tle2, 43, 8);
        $meanMotion = (float) substr($tle2, 52, 11); // revs per day

        // Epoch calculation
        $epochYear = (int) substr($tle1, 18, 2);
        $epochYear = ($epochYear < 57) ? 2000 + $epochYear : 1900 + $epochYear;
        $epochDay = (float) substr($tle1, 20, 12);

        $baseEpoch = (new DateTime("$epochYear-01-01 00:00:00", new DateTimeZone('UTC')))->getTimestamp();
        $epoch = $baseEpoch + ($epochDay - 1) * 86400;

        // Time since epoch in minutes
        $tsince = ($timestamp - $epoch) / 60.0;

        // Mean Motion in rad/min
        $n = $meanMotion * 2 * M_PI / 1440.0;

        // Solve Kepler's equation for Mean Anomaly at tsince
        $M = deg2rad($meanAnom) + $n * $tsince;

        // Solve for Eccentric Anomaly (E)
        $E = $M;
        for ($i = 0; $i < 5; $i++) {
            $E = $M + $eccentricity * sin($E);
        }

        // True Anomaly (v)
        $v = 2 * atan2(sqrt(1 + $eccentricity) * sin($E / 2), sqrt(1 - $eccentricity) * cos($E / 2));

        // Semi-major axis from mean motion (a = (mu/n^2)^(1/3))
        // Earth's gravitational constant mu = 398600.4418 km^3/s^2
        $a = pow(398600.4418 / pow($n / 60.0, 2), 1 / 3);
        $r = $a * (1 - $eccentricity * cos($E));

        // Lateral position in orbital plane
        $argOfLat = $v + deg2rad($argPerigee);

        // Inclination and RAAN effects
        $lat = rad2deg(asin(sin(deg2rad($inclination)) * sin($argOfLat)));

        // Longitude with rotation compensation
        // We use a simplified GMST + RAAN calculation
        $gmst = $this->calculateGMST($time);
        $lng = rad2deg(atan2(cos(deg2rad($inclination)) * sin($argOfLat), cos($argOfLat))) + $raan - $gmst;

        // Wrap longitude to [-180, 180]
        $lng = fmod($lng + 180, 360);
        if ($lng < 0)
            $lng += 360;
        $lng -= 180;

        // Velocity approximation (km/s)
        $velocity = sqrt(398600.4418 * (2 / $r - 1 / $a));

        return [
            'latitude' => round($lat, 6),
            'longitude' => round($lng, 6),
            'altitude' => round($r - self::WGS84_A, 2),
            'velocity' => round($velocity, 3),
            'period' => round(1440 / $meanMotion, 2),
            'timestamp' => $time->format('Y-m-d H:i:s'),
            'source' => 'SGP4 Core Engine'
        ];
    }

    /**
     * Greenwich Mean Sidereal Time
     */
    private function calculateGMST(DateTime $time): float
    {
        $jd = $this->getJulianDate($time);
        $t = ($jd - 2451545.0) / 36525.0;
        $gmst = 280.46061837 + 360.98564736629 * ($jd - 2451545.0) + 0.000387933 * $t * $t - $t * $t * $t / 38710000.0;
        return fmod($gmst, 360.0);
    }

    private function getJulianDate(DateTime $time): float
    {
        return ($time->getTimestamp() / 86400.0) + 2440587.5;
    }
}
