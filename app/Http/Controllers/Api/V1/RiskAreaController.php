<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RiskArea;
use Illuminate\Http\JsonResponse;
use App\Services\TenantManager;

class RiskAreaController extends Controller
{
    protected $tenantManager;

    public function __construct(TenantManager $tenantManager)
    {
        $this->tenantManager = $tenantManager;
    }

    /**
     * Get all active risk areas for the tenant.
     */
    public function index(): JsonResponse
    {
        $tenant = $this->tenantManager->getTenant();

        $areas = RiskArea::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $areas,
            'timestamp' => now()->toIso8601String()
        ]);
    }
}
