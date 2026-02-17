<?php

namespace App\Engines\Weather;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RainViewerService
{
    private const API_URL = 'https://api.rainviewer.com/public/weather-maps.json';

    /**
     * Get the latest radar tiles information.
     */
    public function getLatestRadar(): array
    {
        try {
            $response = Http::get(self::API_URL);
            if ($response->successful()) {
                $data = $response->json();
                $latestRadar = $data['radar']['past'][count($data['radar']['past']) - 1];

                return [
                    'timestamp' => $latestRadar['time'],
                    'path' => $latestRadar['path'],
                    'host' => $data['host'],
                    'tile_size' => $data['tileSize'] ?? 256 // Default to 256 if missing
                ];
            }
        } catch (\Exception $e) {
            Log::error("RainViewer API Error: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Get the URL for a specific tile (XYZ format).
     * Usage: https://{host}{path}/{size}/{z}/{x}/{y}/{color}/{options}.png
     */
    public function getTileUrl(array $config, int $z, int $x, int $y, int $color = 2, int $smooth = 1): string
    {
        return "{$config['host']}{$config['path']}/{$config['tile_size']}/{$z}/{$x}/{$y}/{$color}/{$smooth}_1.png";
    }
}
