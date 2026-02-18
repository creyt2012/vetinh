<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Satellite;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class SatelliteIntelController extends Controller
{
    /**
     * Get real-time health and telemetry summary for a specific satellite.
     */
    public function show($noradId): JsonResponse
    {
        $satellite = Satellite::where('norad_id', $noradId)->firstOrFail();
        $latestPath = "telemetry/{$noradId}/latest.json";

        if (!Storage::disk('public')->exists($latestPath)) {
            return response()->json(['status' => 'error', 'message' => 'Latest telemetry not found'], 404);
        }

        $data = json_decode(Storage::disk('public')->get($latestPath), true);

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    /**
     * Get historical health trends for a specific satellite (Power, Thermal, Link).
     */
    public function history($noradId): JsonResponse
    {
        $directory = "telemetry/{$noradId}";
        $files = Storage::disk('public')->files($directory);

        // Filter for timestamped files and exclude latest.json
        $historyFiles = array_filter($files, function ($file) {
            return preg_match('/\d{8}_\d{6}\.json$/', $file);
        });

        // Sort by timestamp (alphabetical sort works for YYYYMMDD_HHMMSS)
        sort($historyFiles);

        // Take last 24 points for the chart
        $historyFiles = array_slice($historyFiles, -24);

        $trends = [];
        foreach ($historyFiles as $file) {
            $data = json_decode(Storage::disk('public')->get($file), true);
            if (!$data)
                continue;

            $trends[] = [
                'time' => Carbon::parse($data['metadata']['timestamp'])->format('H:i'),
                'power' => (int) str_replace('%', '', $data['subsystems']['power_bus']),
                'thermal' => (int) str_replace('C', '', $data['subsystems']['thermal']),
                'link' => abs((int) str_replace('dBm', '', $data['subsystems']['comm_link']))
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $trends
        ]);
    }
}
