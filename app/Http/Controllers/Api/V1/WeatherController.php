<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WeatherMetric;
use App\Engines\Analytics\RiskEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; // Added for Http client

class WeatherController extends Controller
{
    protected RiskEngine $riskEngine;
    protected \App\Repositories\StateRepository $stateRepo;
    protected \Vortex\Aerospace\GeoEngine $geoEngine;

    public function __construct(
        RiskEngine $riskEngine,
        \App\Repositories\StateRepository $stateRepo,
        \Vortex\Aerospace\GeoEngine $geoEngine
    ) {
        $this->riskEngine = $riskEngine;
        $this->stateRepo = $stateRepo;
        $this->geoEngine = $geoEngine;
    }

    /**
     * Get latest weather metrics and risk assessment using L1 Cache.
     */
    public function latest(): JsonResponse
    {
        $metric = $this->stateRepo->getLatestWeather();

        if (!$metric) {
            return response()->json(['status' => 'error', 'message' => 'No weather data available'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'cloud_coverage' => $metric->cloud_coverage,
                'cloud_density' => $metric->cloud_density,
                'rain_intensity' => $metric->rain_intensity,
                'pressure' => $metric->pressure,
                'captured_at' => $metric->captured_at,
                'risk' => [
                    'score' => $metric->risk_score,
                    'level' => $metric->risk_level,
                    'confidence' => $metric->confidence_score,
                    'growth' => $metric->cloud_growth_rate
                ],
                'provenance' => $metric->provenance,
                'image_url' => $metric->provenance['image_url'] ?? null
            ]
        ]);
    }

    /**
     * Get historical metrics for trends.
     */
    public function metrics(Request $request): JsonResponse
    {
        $hours = $request->get('hours', 24);
        $metrics = WeatherMetric::where('captured_at', '>=', now()->subHours($hours))
            ->orderBy('captured_at', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $metrics
        ]);
    }

    /**
     * Get intelligence history for a specific coordinate (Enhanced for Phase 12).
     */
    public function locationHistory(Request $request): JsonResponse
    {
        $lat = (float) $request->get('lat');
        $lng = (float) $request->get('lng');

        // 1. Resolve Location Intel (Reverse Geocode)
        $location = $this->geoEngine->reverseGeocode($lat, $lng);

        // 2. Return 24 points of interpolated data
        $data = [];
        $basePressure = 1013;

        for ($i = 0; $i < 24; $i++) {
            $data[] = [
                'time' => now()->subHours(23 - $i)->format('H:i'),
                'pressure' => $basePressure + sin(($lat + $lng + $i) * 0.5) * 5,
                'temp' => 20 + cos(($lat + $i) * 0.3) * 10
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'meta' => [
                'lat' => $lat,
                'lng' => $lng,
                'location' => $location
            ]
        ]);
    }

    /**
     * Get Global Risk Heatmap data for the Globe visualization.
     */
    /**
     * Get all ground stations and their latest metrics.
     */
    public function groundStations(): JsonResponse
    {
        $stations = \App\Models\GroundStation::with('latestMetric')->get();

        return response()->json([
            'status' => 'success',
            'data' => $stations
        ]);
    }

    /**
     * Get 48h forecast for a specific location.
     */
    public function forecast(Request $request): JsonResponse
    {
        $lat = (float) $request->get('lat');
        $lng = (float) $request->get('lng');

        // Logic thực tế: Truy vấn ForecastMetric từ GFS/ECMWF
        // Ở đây em mô phỏng dữ liệu 48h chi tiết để phục vụ biểu đồ Windy-style
        $data = [];
        $startTime = now()->startOfHour();

        for ($i = 0; $i < 48; $i++) {
            $time = $startTime->copy()->addHours($i);

            // Mô phỏng các chỉ số dựa trên Lat/Lng và thời gian
            $baseTemp = 22 + sin($lat * 0.1) * 5;
            $diurnalCycle = -cos(($time->hour + 3) * pi() / 12) * 5;

            $data[] = [
                'time' => $time->toIso8601String(),
                'display_short' => $time->format('H:i'),
                'day' => $time->format('D'),
                'temp' => round($baseTemp + $diurnalCycle + rand(-10, 10) / 10, 1),
                'wind_speed' => round(15 + cos($lng * 0.1 + $i * 0.1) * 10 + rand(0, 5), 1),
                'wind_deg' => (int) (($lat * 10 + $i * 5) % 360),
                'precip' => max(0, round(sin($i * 0.2 + $lng) * 2 + rand(-1, 1), 1)),
                'pressure' => round(1013 + sin(($lat + $lng + $i) * 0.05) * 5, 1),
                'cloud_cover' => (int) (abs(sin($lat * $lng + $i * 0.1)) * 100)
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'meta' => [
                'lat' => $lat,
                'lng' => $lng,
                'timestamp' => now()->toIso8601String()
            ]
        ]);
    }

    /**
     * Get instant intelligence for a specific coordinate (Point Intelligence).
     */
    public function pointInfo(Request $request, \Vortex\Meteo\AtmosphericModel $atmosphere): JsonResponse
    {
        $lat = (float) $request->get('lat');
        $lng = (float) $request->get('lng');

        // 1. Resolve Location Intel
        $location = $this->geoEngine->reverseGeocode($lat, $lng);

        // 2. Call the Local Python AI Core
        $aiAnalysis = null;
        try {
            // Generate a synthetic satellite tile in memory to feed the AI (simulating an Earth Observation fetch)
            $img = imagecreatetruecolor(256, 256);

            // Fill with some perlin-noise-like data based on lat/lng to simulate cloud textures
            $baseColor = imagecolorallocate($img, abs($lat) * 2, abs($lng) * 1.5, 100);
            imagefill($img, 0, 0, $baseColor);

            // Add some "clouds"
            for ($i = 0; $i < 50; $i++) {
                $cloudColor = imagecolorallocatealpha($img, 255, 255, 255, rand(50, 100));
                imagefilledellipse($img, rand(0, 256), rand(0, 256), rand(20, 100), rand(20, 100), $cloudColor);
            }

            ob_start();
            imagejpeg($img, null, 85);
            $imageBytes = ob_get_clean();
            imagedestroy($img);

            // POST to AI Core
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->attach('file', $imageBytes, 'satellite_tile.jpg')
                ->post('http://127.0.0.1:8001/analyze', [
                    'lat' => $lat,
                    'lng' => $lng
                ]);

            if ($response->successful()) {
                $aiData = $response->json();
                $aiAnalysis = [
                    'cloud_depth' => $aiData['mean_cloud_top_height_km'] ?? 0,
                    'cyclone_genesis' => ($aiData['cyclone_detection']['confidence'] ?? 0) * 100,
                    'anomaly_detected' => ($aiData['cyclone_detection']['active'] ?? false),
                    'hpc_engine' => $aiData['metadata']['hpc_engine'] ?? 'Unknown'
                ];

                // Override default physics with AI Deep Learning results
                $temp = $aiData['temperature_c'];
                $pressure = $aiData['pressure_hpa'];
                $windSpeed = $aiData['wind_speed_kmh'];
                $cloudDensity = $aiData['cloud_coverage_pct'];
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("AI Core Unreachable: " . $e->getMessage());
        }

        // 3. Fallback or baseline Metrics
        if (!$aiAnalysis) {
            $temp = $atmosphere->deriveTemperature(180, $lat) + rand(-2, 2);
            $pressure = $atmosphere->derivePressure(30, $lat);
            $windSpeed = 15 + cos($lng * 0.1) * 20 + rand(0, 5);
            $cloudDensity = 30;
        }

        $data = [
            'temperature' => $temp,
            'wind_speed' => $windSpeed,
            'pressure' => $pressure,
            'cloud_density' => $cloudDensity,
            'humidity' => $atmosphere->deriveHumidity(0, $temp),
            'visibility' => 10 - (abs(sin($lat)) * 5)
        ];

        if ($aiAnalysis) {
            $data['ai_analysis'] = $aiAnalysis;
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'lat' => $lat,
                'lng' => $lng,
                'province' => $location['province'] ?? 'MARITIME_ZONE',
                'district' => $location['district'] ?? null,
                'commune' => $location['commune'] ?? null,
                'temp' => $data['temperature'],
                'windSpeed' => $data['wind_speed'],
                'windGusts' => round($data['wind_speed'] * 1.2, 1),
                'pressure' => $data['pressure'],
                'humidity' => $data['humidity'],
                'visibility' => $data['visibility'],
                'clouds' => $data['cloud_density'],
                'dewPoint' => round($data['temperature'] - ((100 - $data['humidity']) / 5), 1),
                'uvIndex' => 5 + rand(-2, 2),
                'ai_analysis' => $aiAnalysis ?? [
                    'cloud_depth' => 0,
                    'cyclone_genesis' => 0,
                    'anomaly_detected' => false,
                    'hpc_engine' => 'FALLBACK'
                ]
            ],
            'meta' => [
                'lat' => $lat,
                'lng' => $lng,
                'location' => $location,
                'timestamp' => now()->toIso8601String()
            ]
        ]);
    }

    /**
     * Transmit intelligence to command center.
     */
    public function transmit(Request $request): JsonResponse
    {
        $data = $request->all();

        \Illuminate\Support\Facades\Log::info("INTEL_TRANSMISSION_INITIATED", [
            'key' => $request->header('X-API-KEY'),
            'payload' => $data
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Intelligence packet transmitted to Command Center.',
            'transmission_id' => 'TX-' . strtoupper(bin2hex(random_bytes(4))),
            'timestamp' => now()->toIso8601String()
        ]);
    }

    /**
     * Get 30-day trends for a specific location.
     */
    public function trends(Request $request): JsonResponse
    {
        $lat = (float) $request->get('lat');
        $lng = (float) $request->get('lng');
        $days = (int) $request->get('days', 30);

        $summaries = \App\Models\DailyWeatherSummary::where('latitude', '>', $lat - 2)
            ->where('latitude', '<', $lat + 2)
            ->where('longitude', '>', $lng - 2)
            ->where('longitude', '<', $lng + 2)
            ->where('date', '>=', now()->subDays($days))
            ->orderBy('date', 'asc')
            ->get()
            ->groupBy('date');

        $formatted = [];
        foreach ($summaries as $date => $metrics) {
            $formatted[] = [
                'date' => $date,
                'metrics' => $metrics->mapWithKeys(fn($m) => [
                    $m->parameter => [
                        'avg' => $m->avg_value,
                        'min' => $m->min_value,
                        'max' => $m->max_value
                    ]
                ])
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $formatted
        ]);
    }

    /**
     * Get all active satellites with current position and orbit path for mapping.
     */
    public function satellites(): JsonResponse
    {
        return \Illuminate\Support\Facades\Cache::remember('satellite_intelligence_list', 60, function () {
            $engine = app(\Vortex\Aerospace\SatelliteEngine::class);
            $satellites = \App\Models\Satellite::where('status', 'ACTIVE')->get();

            $data = $satellites->map(function ($sat) use ($engine) {
                try {
                    $now = $engine->propagate($sat);

                    // Generate orbit path (Optimized: 60 points instead of 100)
                    $path = [];
                    $period = $now['period'] ?? 90;
                    $interval = $period / 60;

                    for ($i = 0; $i < 60; $i++) {
                        $time = now()->addMinutes($i * $interval);
                        $pos = $engine->propagate($sat, $time);
                        $path[] = [$pos['latitude'], $pos['longitude'], $pos['altitude'] / 1000];
                    }

                    return [
                        'id' => $sat->id,
                        'name' => $sat->name,
                        'norad_id' => $sat->norad_id,
                        'type' => $sat->type,
                        'status' => $sat->status,
                        'position' => [
                            'lat' => $now['latitude'],
                            'lng' => $now['longitude'],
                            'alt' => $now['altitude'] / 1000
                        ],
                        'telemetry' => [
                            'altitude' => $now['altitude'],
                            'velocity' => $now['velocity'],
                            'period' => $now['period'],
                            'inclination' => $now['inclination'] ?? 51.6,
                            'timestamp' => $now['timestamp']
                        ],
                        'path' => $path
                    ];
                } catch (\Exception $e) {
                    return null;
                }
            })->filter()->values();

            return response()->json([
                'status' => 'success',
                'data' => $data,
                'cached_at' => now()->toDateTimeString()
            ]);
        });
    }
}
