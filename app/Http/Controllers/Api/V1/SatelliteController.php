<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Satellite;
use App\Models\SatelliteTrack;
use Illuminate\Http\JsonResponse;

class SatelliteController extends Controller
{
    protected \App\Repositories\StateRepository $stateRepo;

    protected \Vortex\Aerospace\SatelliteEngine $engine;

    public function __construct(
        \App\Repositories\StateRepository $stateRepo,
        \Vortex\Aerospace\SatelliteEngine $engine
    ) {
        $this->stateRepo = $stateRepo;
        $this->engine = $engine;
    }

    /**
     * Get live positions of all active satellites using L1 Cache.
     */
    public function index(): JsonResponse
    {
        $states = $this->stateRepo->getSatelliteStates();

        if (empty($states)) {
            // Fallback to DB if cache is cold
            return $this->indexFromDb();
        }

        return response()->json([
            'status' => 'success',
            'data' => array_values($states)
        ]);
    }

    public function telemetry(Satellite $satellite): JsonResponse
    {
        try {
            $prop = $this->engine->propagate($satellite);

            // Fetch latest environmental data from this specific unit
            $latestMetric = \App\Models\WeatherMetric::where('source', 'LIKE', "%{$satellite->name}%")
                ->latest('captured_at')
                ->first();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $satellite->id,
                    'name' => $satellite->name,
                    'norad_id' => $satellite->norad_id,
                    'telemetry' => [
                        'latitude' => $prop['latitude'],
                        'longitude' => $prop['longitude'],
                        'altitude' => $prop['altitude'],
                        'velocity' => $prop['velocity'],
                        'period' => $prop['period'],
                        'timestamp' => $prop['timestamp']
                    ],
                    'payload' => $latestMetric ? [
                        'temperature' => $latestMetric->provenance['temp_derived'] ?? null,
                        'brightness' => $latestMetric->provenance['mean_brightness'] ?? 180,
                        'pressure' => $latestMetric->pressure,
                        'cloud_coverage' => $latestMetric->cloud_coverage,
                        'wind_speed' => $latestMetric->provenance['wind_speed'] ?? null,
                        'captured_at' => $latestMetric->captured_at->toIso8601String()
                    ] : null
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Telemetry link severed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get historical imagery for a specific location (Satellite Time Machine).
     */
    public function imageryHistory(\Illuminate\Http\Request $request): JsonResponse
    {
        $lat = (float) $request->get('lat');
        $lng = (float) $request->get('lng');
        $radius = (float) $request->get('radius', 5.0); // degree radius

        $history = \App\Models\WeatherMetric::whereNotNull('provenance->image_id')
            ->whereBetween('latitude', [$lat - $radius, $lat + $radius])
            ->whereBetween('longitude', [$lng - $radius, $lng + $radius])
            ->orderBy('captured_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'timestamp' => $m->captured_at->toIso8601String(),
                    'image_url' => asset('storage/weather/' . ($m->provenance['image_id'] ?? 'himawari_latest.png')),
                    'metrics' => [
                        'temp' => $m->provenance['temp_derived'] ?? null,
                        'coverage' => $m->cloud_coverage
                    ]
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $history
        ]);
    }

    /**
     * Get Two-Line Element (TLE) set for the unit.
     */
    public function tle(Satellite $satellite): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'satellite' => $satellite->name,
                'norad_id' => $satellite->norad_id,
                'tle' => [
                    'line1' => $satellite->tle_line1,
                    'line2' => $satellite->tle_line2,
                    'updated_at' => $satellite->updated_at->toIso8601String()
                ]
            ]
        ]);
    }

    private function indexFromDb(): JsonResponse
    {
        $satellites = Satellite::where('status', 'ACTIVE')
            ->get()
            ->map(function ($sat) {
                $latestTrack = SatelliteTrack::where('satellite_id', $sat->id)
                    ->latest('tracked_at')
                    ->first();

                $trackData = app(\Vortex\Aerospace\SatelliteEngine::class)->propagate($sat);
                $path = app(\Vortex\Aerospace\SatelliteEngine::class)->getOrbitPath($sat);

                return [
                    'id' => $sat->id,
                    'name' => $sat->name,
                    'norad_id' => $sat->norad_id,
                    'type' => $sat->type,
                    'path' => $path,
                    'telemetry' => [
                        'period' => $trackData['period'] ?? 0,
                        'velocity' => $trackData['velocity'] ?? 0,
                    ],
                    'last_track' => $latestTrack ? [
                        'latitude' => $latestTrack->latitude,
                        'longitude' => $latestTrack->longitude,
                        'altitude' => $latestTrack->altitude,
                        'velocity' => $latestTrack->velocity,
                        'timestamp' => $latestTrack->tracked_at,
                        'source' => $latestTrack->source ?? 'Authentic Telemetry'
                    ] : [
                        'latitude' => $trackData['latitude'],
                        'longitude' => $trackData['longitude'],
                        'altitude' => $trackData['altitude'],
                        'velocity' => $trackData['velocity'],
                        'timestamp' => $trackData['timestamp'],
                        'source' => 'Calculated Baseline'
                    ]
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $satellites
        ]);
    }
}
