<?php

namespace Vortex\Meteo;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class EumetsatService
{
    /**
     * Download the latest Meteosat imagery from EUMETSAT Data Portal.
     * Meteosat-9: 28912
     */
    public function downloadLatest(string $noradId = '28912'): ?string
    {
        try {
            // Updated URL for EUMETSAT static imagery
            $url = "https://eumetview.eumetsat.int/static-images/METEOSAT/RGB/NATURALCOLOR/FULL_RESOLUTION/latest.jpg";

            // Fallback to lower res if full fails
            $response = Http::timeout(120)->get($url);

            if ($response->failed()) {
                $url = "https://eumetview.eumetsat.int/static-images/METEOSAT/RGB/NATURALCOLOR/latest.jpg";
                $response = Http::timeout(120)->get($url);
            }

            if ($response->failed()) {
                Log::warning("EUMETSAT download failed for NORAD {$noradId}: {$url}");
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
            // Meteosat-9 is typically at 45.5° East for IODC (Indian Ocean Data Coverage) or 0° for Prime
            $lon = match ($noradId) {
                '28912' => 45.5, // Meteosat-9
                default => 0.0,
            };

            // 3. Trigger L4 AI Core Subsystem (Reprojection Engine)
            $pythonScript = base_path('ai_core/core/pipelines/reproject_geostationary.py');
            if (file_exists($pythonScript)) {
                Log::info("EUMETSAT: Reprojecting NORAD {$noradId} from Geostationary to Equirectangular...");
                $cmd = escapeshellcmd("python3 {$pythonScript} --input {$absoluteRawPath} --output {$absoluteFinalPath} --lon {$lon}");
                $output = shell_exec($cmd . " 2>&1");
                Log::info("EUMETSAT L4 Output: " . $output);

                // If reprojection succeeded, copy to latest
                if (file_exists($absoluteFinalPath)) {
                    copy($absoluteFinalPath, Storage::disk('public')->path($latestPath));
                } else {
                    Log::warning("EUMETSAT L4 Reprojection Failed, falling back to raw disk.");
                    Storage::disk('public')->put($latestPath, $response->body());
                }
            } else {
                Log::warning("EUMETSAT: L4 Engine not found, falling back to raw disk.");
                Storage::disk('public')->put($latestPath, $response->body());
            }

            return Storage::disk('public')->path($latestPath);
        } catch (\Exception $e) {
            Log::error("EUMETSAT Service Error: " . $e->getMessage());
            return null;
        }
    }
}
