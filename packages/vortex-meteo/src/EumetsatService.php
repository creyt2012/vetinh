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
            $path = "imagery/{$noradId}/{$timestamp}.jpg";
            Storage::disk('public')->put($path, $response->body());

            // Pointer for latest
            Storage::disk('public')->put("imagery/{$noradId}/latest.jpg", $response->body());

            return Storage::disk('public')->path($path);
        } catch (\Exception $e) {
            Log::error("EUMETSAT Service Error: " . $e->getMessage());
            return null;
        }
    }
}
