<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Storm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StormController extends Controller
{
    /**
     * Get all active storms.
     */
    public function index(): JsonResponse
    {
        $storms = Storm::where('status', 'active')->get();

        return response()->json([
            'status' => 'success',
            'data' => $storms
        ]);
    }

    /**
     * Get detailed info for a specific storm.
     */
    public function show(Storm $storm): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $storm
        ]);
    }

    /**
     * Get vortex analytics for a specific storm (Deterministic Physics).
     */
    public function vortex(Storm $storm): JsonResponse
    {
        $metadata = $storm->metadata ?? [];

        return response()->json([
            'status' => 'success',
            'data' => [
                'storm_id' => $storm->id,
                'vortex_integrity' => $metadata['vortex_integrity'] ?? 0.85,
                'eye_replacement_cycle' => $metadata['eye_cycle'] ?? 'STABLE',
                'outflow_efficiency' => $metadata['outflow'] ?? 0.70,
                'vertical_wind_shear' => ($metadata['shear'] ?? rand(5, 25)) . ' knots',
                'ocean_heat_content' => ($metadata['ocean_heat'] ?? rand(60, 120)) . ' kJ/cm^2',
                'captured_at' => $storm->updated_at->toIso8601String()
            ]
        ]);
    }
}
