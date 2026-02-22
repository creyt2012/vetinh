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
        $vessels = \App\Models\Vessel::all()->map(function ($vessel) {
            return [
                'mmsi' => $vessel->mmsi,
                'name' => $vessel->name,
                'type' => $vessel->type,
                'latitude' => (float) $vessel->latitude,
                'longitude' => (float) $vessel->longitude,
                'speed_knots' => (float) $vessel->speed,
                'course' => (float) $vessel->heading,
                'status' => $vessel->status,
                'metadata' => $vessel->metadata
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $vessels,
            'source' => 'Global AIS Mesh',
            'timestamp' => now()->toIso8601String()
        ]);
    }
}
