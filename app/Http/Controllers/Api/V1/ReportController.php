<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MissionFile;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    /**
     * List all available meteorological reports.
     */
    public function index(): JsonResponse
    {
        $reports = MissionFile::where('type', 'METEOROLOGICAL_REPORT')
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $reports
        ]);
    }

    /**
     * Get download metadata for a specific report.
     */
    public function download(MissionFile $file): JsonResponse
    {
        if ($file->type !== 'METEOROLOGICAL_REPORT') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized resource type'], 403);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Report stream auth-key generated',
            'data' => [
                'filename' => $file->filename,
                'download_url' => url("/reports/{$file->id}/download"),
                'expires_at' => now()->addMinutes(15)->toIso8601String()
            ]
        ]);
    }
}
