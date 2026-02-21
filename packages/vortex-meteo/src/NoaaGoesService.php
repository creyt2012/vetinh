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
            $rawPath = "imagery/{$noradId}/{$timestamp}_raw.jpg";
            $finalPath = "imagery/{$noradId}/{$timestamp}.jpg";
            $latestPath = "imagery/{$noradId}/latest.jpg";

            // 1. Save RAW Geostationary Image
            Storage::disk('public')->put($rawPath, $response->body());
            $absoluteRawPath = Storage::disk('public')->path($rawPath);
            $absoluteFinalPath = Storage::disk('public')->path($finalPath);

            // 2. Determine Sub-Satellite Longitude
            // GOES-16 (East) is at -75.2, GOES-19 (East) is -75.0, GOES-17 (West) is -137.2, GOES-18 (West) is -137.0
            $lon = match ($noradId) {
                '60133' => -75.0,
                '41866' => -75.2,
                '43226' => -137.2,
                '53461' => -137.0,
                default => -75.0,
            };

            // 3. Trigger L4 AI Core Subsystem (Reprojection Engine)
            $pythonScript = base_path('ai_core/core/pipelines/reproject_geostationary.py');
            if (file_exists($pythonScript)) {
                Log::info("NOAA GOES: Reprojecting {$satelliteCode} from Geostationary to Equirectangular...");
                $cmd = escapeshellcmd("python3 {$pythonScript} --input {$absoluteRawPath} --output {$absoluteFinalPath} --lon {$lon}");
                $output = shell_exec($cmd . " 2>&1");
                Log::info("NOAA GOES L4 Output: " . $output);

                // If reprojection succeeded, copy to latest
                if (file_exists($absoluteFinalPath)) {
                    copy($absoluteFinalPath, Storage::disk('public')->path($latestPath));
                } else {
                    Log::warning("NOAA GOES L4 Reprojection Failed, falling back to raw disk.");
                    Storage::disk('public')->put($latestPath, $response->body());
                }
            } else {
                Log::warning("NOAA GOES: L4 Engine not found, falling back to raw disk.");
                Storage::disk('public')->put($latestPath, $response->body());
            }

            return Storage::disk('public')->path($latestPath);
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
