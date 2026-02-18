<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class MarineController extends Controller
{
    /**
     * Get all tracked vessels (Marine Traffic Intelligence).
     */
    public function index(): JsonResponse
    {
        // Simulate AIS tracking data
        $vessels = [];
        $types = ['CONTAINER_SHIP', 'OIL_TANKER', 'CARGO_VESSEL', 'RESEARCH_UNIT'];

        for ($i = 0; $i < 50; $i++) {
            $vessels[] = [
                'mmsi' => '244' . rand(100000, 999999),
                'name' => 'STAR_UNIT_' . ($i + 100),
                'type' => $types[rand(0, 3)],
                'latitude' => (rand(-600, 600) / 10),
                'longitude' => (rand(-1800, 1800) / 10),
                'speed_knots' => rand(10, 25),
                'course' => rand(0, 360),
                'destination' => 'COASTAL_SECTOR_' . chr(rand(65, 90)),
                'eta' => now()->addDays(rand(1, 5))->toIso8601String()
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $vessels,
            'source' => 'Global AIS Mesh',
            'timestamp' => now()->toIso8601String()
        ]);
    }
}
