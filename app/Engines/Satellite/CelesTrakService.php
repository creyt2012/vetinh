<?php

namespace App\Engines\Satellite;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CelesTrakService
{
    /**
     * Common TLE sets from CelesTrak
     */
    private const TLE_URLS = [
        'ACTIVE' => 'https://celestrak.org/NORAD/elements/gp.php?GROUP=active&FORMAT=tle',
        'STATIONS' => 'https://celestrak.org/NORAD/elements/gp.php?GROUP=stations&FORMAT=tle',
        'WEATHER' => 'https://celestrak.org/NORAD/elements/gp.php?GROUP=weather&FORMAT=tle',
        'GPS' => 'https://celestrak.org/NORAD/elements/gp.php?GROUP=gps-ops&FORMAT=tle',
    ];

    /**
     * Fetch all TLEs from a specific group
     */
    public function fetchGroup(string $group = 'ACTIVE'): array
    {
        $url = self::TLE_URLS[$group] ?? self::TLE_URLS['ACTIVE'];

        try {
            $response = Http::timeout(30)->get($url);

            if (!$response->successful()) {
                Log::error("Failed to fetch TLEs from CelesTrak: " . $response->status());
                return [];
            }

            return $this->parseTleData($response->body());
        } catch (\Exception $e) {
            Log::error("CelesTrak API Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Parse raw 3-line TLE format into an array
     */
    private function parseTleData(string $raw): array
    {
        $lines = explode("\n", str_replace("\r", "", trim($raw)));
        $satellites = [];

        for ($i = 0; $i < count($lines); $i += 3) {
            if (!isset($lines[$i + 1]) || !isset($lines[$i + 2]))
                break;

            $name = trim($lines[$i]);
            $line1 = trim($lines[$i + 1]);
            $line2 = trim($lines[$i + 2]);

            // Extract NORAD ID from Line 1 (Columns 3-7)
            $noradId = trim(substr($line1, 2, 5));

            $satellites[$noradId] = [
                'name' => $name,
                'tle_line1' => $line1,
                'tle_line2' => $line2,
                'norad_id' => $noradId,
            ];
        }

        return $satellites;
    }

    /**
     * Get TLE for a specific NORAD ID
     */
    public function getSpecificTle(string $noradId): ?array
    {
        $url = "https://celestrak.org/NORAD/elements/gp.php?CATNR={$noradId}&FORMAT=tle";

        try {
            $response = Http::get($url);
            if ($response->successful()) {
                $parsed = $this->parseTleData($response->body());
                return $parsed[$noradId] ?? null;
            }
        } catch (\Exception $e) {
            Log::error("Failed to get specific Tle for {$noradId}: " . $e->getMessage());
        }

        return null;
    }
}
