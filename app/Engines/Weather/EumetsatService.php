<?php

namespace App\Engines\Weather;

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
            // Using EUMETSAT public imagery preview service
            // This is a proxy to their Real-Time Imagery service
            $url = "https://eumetview.eumetsat.int/static-images/METEOSAT/RGB/NATURALCOLOR/FULLRESOLUTION/latest.jpg";

            $response = Http::timeout(60)->get($url);

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
