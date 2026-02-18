<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GroundStation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroundStationController extends Controller
{
    /**
     * List all ground stations.
     */
    public function index(): JsonResponse
    {
        $stations = GroundStation::with('latestMetric')->get();

        return response()->json([
            'status' => 'success',
            'data' => $stations
        ]);
    }

    /**
     * Create a new ground station.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'status' => 'required|string',
            'metadata' => 'nullable|array'
        ]);

        $station = GroundStation::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Station initialized',
            'data' => $station
        ], 201);
    }

    /**
     * Show a specific station.
     */
    public function show(GroundStation $groundStation): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $groundStation->load('latestMetric')
        ]);
    }

    /**
     * Update a ground station.
     */
    public function update(Request $request, GroundStation $groundStation): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'latitude' => 'sometimes|numeric',
            'longitude' => 'sometimes|numeric',
            'status' => 'sometimes|string',
            'metadata' => 'sometimes|nullable|array'
        ]);

        $groundStation->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Station patched',
            'data' => $groundStation
        ]);
    }

    /**
     * Delete a ground station.
     */
    public function destroy(GroundStation $groundStation): JsonResponse
    {
        $groundStation->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Station decommissioned'
        ]);
    }
}
