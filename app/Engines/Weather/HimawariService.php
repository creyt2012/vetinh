<?php

namespace App\Engines\Weather;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class HimawariService
{
    /**
     * Download the latest Himawari-9 full disk or sector image dynamically.
     */
    public function downloadLatest(): string
    {
        try {
            // 1. Fetch latest timestamp info from NICT
            $latestInfo = Http::get("https://himawari8-dl.nict.go.jp/himawari8/img/D531106/latest.json");

            if ($latestInfo->failed()) {
                throw new \Exception("Could not fetch latest Himawari timestamp");
            }

            $dateStr = $latestInfo->json()['date']; // e.g., "2026-02-17 00:00:00"
            $date = \Carbon\Carbon::parse($dateStr);

            // 2. Construct dynamic URL based on latest timestamp
            // Path format: /1d/800/YYYY/MM/DD/HHMMSS_0_0.png
            $year = $date->format('Y');
            $month = $date->format('m');
            $day = $date->format('d');
            $time = $date->format('His');

            $url = "https://himawari8-dl.nict.go.jp/himawari8/img/D531106/1d/800/{$year}/{$month}/{$day}/{$time}_0_0.png";

            $response = Http::timeout(30)->get($url);

            if ($response->failed()) {
                Log::warning("Himawari image download failed for URL: {$url}");
                return $this->usePlaceholder();
            }

            $path = "imagery/41836/{$year}{$month}{$day}_{$time}.png";
            Storage::disk('public')->put($path, $response->body());

            // Also update the latest pointer for the current map view
            Storage::disk('public')->put('imagery/41836/latest.png', $response->body());
            Storage::disk('public')->put('weather/himawari_latest.png', $response->body()); // Legacy support

            return Storage::disk('public')->path($path);
        } catch (\Exception $e) {
            Log::error("Himawari Service Error: " . $e->getMessage());
            return $this->usePlaceholder();
        }
    }

    private function usePlaceholder(): string
    {
        $path = storage_path('app/weather/placeholder.jpg');
        if (!file_exists($path)) {
            // Create a grey placeholder if missing
            $img = imagecreatetruecolor(800, 800);
            $grey = imagecolorallocate($img, 100, 100, 100);
            imagefill($img, 0, 0, $grey);
            if (!is_dir(dirname($path)))
                mkdir(dirname($path), 0755, true);
            imagejpeg($img, $path);
            imagedestroy($img);
        }
        return $path;
    }
}
