<?php

namespace Vortex\Meteo;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class NoaaGoesService
{
    /**
     * Download the latest GOES-East or GOES-West sector image.
     * GOES-East: 60133 (GOES-19), 41866 (GOES-16)
     */
    public function downloadLatest(string $noradId = '60133'): ?string
    {
        try {
            // Using NOAA STAR imagery service as a reliable public source
            // Example GOES-East Full Disk URL structure:
            // https://cdn.star.nesdis.noaa.gov/GOES19/ABI/FD/GEOCOLOR/latest.jpg

            $satelliteCode = $this->resolveSatelliteCode($noradId);
            $url = "https://cdn.star.nesdis.noaa.gov/{$satelliteCode}/ABI/FD/GEOCOLOR/latest.jpg";

            $response = Http::timeout(180)->get($url);

            if ($response->failed()) {
                Log::warning("NOAA GOES download failed for {$satelliteCode}: {$url}");
                return null;
            }

            $timestamp = now()->format('Ymd_His');
            $path = "imagery/{$noradId}/{$timestamp}.jpg";
            Storage::disk('public')->put($path, $response->body());

            // Pointer for latest
            Storage::disk('public')->put("imagery/{$noradId}/latest.jpg", $response->body());

            return Storage::disk('public')->path($path);
        } catch (\Exception $e) {
            Log::error("NOAA GOES Service Error: " . $e->getMessage());
            return null;
        }
    }

    private function resolveSatelliteCode(string $noradId): string
    {
        return match ($noradId) {
            '60133' => 'GOES19',
            '41866' => 'GOES16',
            '43226' => 'GOES17',
            '53461' => 'GOES18', // GOES-18
            default => 'GOES19'
        };
    }
}
