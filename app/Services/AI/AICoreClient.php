<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AICoreClient
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.ai_core.url', env('AI_CORE_URL', 'http://localhost:8001'));
    }

    /**
     * Send imagery to AI Core for deep analysis.
     */
    public function analyzeImagery(string $imagePath, float $lat = 0, float $lng = 0): ?array
    {
        try {
            if (!file_exists($imagePath)) {
                Log::error("AI Core Client: Image not found at {$imagePath}");
                return null;
            }

            $response = Http::timeout(60)
                ->attach('file', file_get_contents($imagePath), basename($imagePath))
                ->post("{$this->baseUrl}/analyze", [
                    'lat' => $lat,
                    'lng' => $lng,
                ]);

            if ($response->failed()) {
                Log::error("AI Core API Failed: " . $response->body());
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("AI Core Client Exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if the AI Core service is healthy.
     */
    public function isOperational(): bool
    {
        try {
            $response = Http::get($this->baseUrl);
            return $response->status() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }
}
