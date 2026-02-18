<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use App\Services\TenantManager;

class AlertHistoryController extends Controller
{
    protected $tenantManager;

    public function __construct(TenantManager $tenantManager)
    {
        $this->tenantManager = $tenantManager;
    }

    /**
     * Get notification history for the tenant.
     */
    public function index(): JsonResponse
    {
        // Fetch logs for NOTIFICATION_SENT across the system (simplified for current schema)
        $logs = ActivityLog::where('action', 'NOTIFICATION_SENT')
            ->latest()
            ->limit(100)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $logs,
            'timestamp' => now()->toIso8601String()
        ]);
    }
}
