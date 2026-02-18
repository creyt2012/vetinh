<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Satellite;
use App\Models\SatelliteTrack;
use Illuminate\Http\JsonResponse;

class SatelliteController extends Controller
{
    protected \App\Repositories\StateRepository $stateRepo;

    protected \App\Engines\Satellite\SatelliteEngine $engine;

    public function __construct(
        \App\Repositories\StateRepository $stateRepo,
        \App\Engines\Satellite\SatelliteEngine $engine
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
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Telemetry link severed'], 500);
        }
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

                return [
                    'id' => $sat->id,
                    'name' => $sat->name,
                    'norad_id' => $sat->norad_id,
                    'last_track' => $latestTrack ? [
                        'latitude' => $latestTrack->latitude,
                        'longitude' => $latestTrack->longitude,
                        'altitude' => $latestTrack->altitude,
                        'velocity' => $latestTrack->velocity,
                        'timestamp' => $latestTrack->tracked_at,
                        'source' => $latestTrack->source ?? 'Authentic Telemetry'
                    ] : null
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $satellites
        ]);
    }
}
