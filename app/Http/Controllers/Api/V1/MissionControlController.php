<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MissionFile;
use App\Services\Ingestion\MissionControlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MissionControlController extends Controller
{
    protected $missionControl;

    public function __construct(MissionControlService $missionControl)
    {
        $this->missionControl = $missionControl;
    }

    /**
     * List mission files for the current tenant.
     */
    public function index(Request $request)
    {
        $files = MissionFile::where('tenant_id', $request->user()->tenant_id)
            ->latest()
            ->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $files
        ]);
    }

    /**
     * Upload a new mission file.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:20480', // 20MB limit
            'type' => 'required|string|in:EXCEL_WEATHER,GEOJSON_RISK'
        ]);

        $file = $this->missionControl->upload(
            $request->file('file'),
            $request->type,
            $request->user()->tenant_id
        );

        // Auto-process for now
        $this->missionControl->process($file);

        return response()->json([
            'status' => 'success',
            'message' => 'File uploaded and queued for processing.',
            'data' => $file
        ]);
    }

    /**
     * Get processing status of a specific file.
     */
    public function show(MissionFile $missionFile)
    {
        // Simple auth check
        if ($missionFile->tenant_id !== Auth::user()->tenant_id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => $missionFile
        ]);
    }
}
