<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/System/AuditLogs', [
            'logs' => ActivityLog::with('user')->latest()->paginate(50)
        ]);
    }
}
