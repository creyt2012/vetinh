<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\MissionFile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('User/Reports/Index', [
            'reports' => MissionFile::where('type', 'METEOROLOGICAL_REPORT')->latest()->get()
        ]);
    }

    public function download(MissionFile $file)
    {
        // Mock download logic
        return response()->json(['message' => 'Report stream initiated', 'file' => $file->filename]);
    }
}
