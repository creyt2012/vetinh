<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\SystemHealth;

class HealthController extends Controller
{
    /**
     * Check system health (DB, Redis, Queue).
     */
    public function check(): JsonResponse
    {
        $status = [
            'status' => 'operational',
            'timestamp' => now()->toIso8601String(),
            'services' => [
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
                'vitals' => [
                    'php_version' => PHP_VERSION,
                    'environment' => app()->environment(),
                ]
            ]
        ];

        $statusCode = $this->isOperational($status) ? 200 : 503;

        return response()->json($status, $statusCode);
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'connected', 'latency' => $this->measure(fn() => DB::select('SELECT 1'))];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkCache(): array
    {
        try {
            Cache::put('health_check', true, 10);
            return ['status' => 'operational', 'alive' => Cache::has('health_check')];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function measure(callable $callback): string
    {
        $start = microtime(true);
        $callback();
        return round((microtime(true) - $start) * 1000, 2) . 'ms';
    }

    private function isOperational(array $status): bool
    {
        return $status['services']['database']['status'] === 'connected'
            && $status['services']['cache']['status'] === 'operational';
    }

    /**
     * Get detailed system performance metrics (SLA).
     */
    public function systemMetrics(): JsonResponse
    {
        $services = ['Database', 'Redis', 'API Gateway'];
        $data = [];

        foreach ($services as $service) {
            $latest = SystemHealth::where('service_name', $service)->latest('recorded_at')->first();
            $data[$service] = [
                'status' => $latest->status ?? 'UNKNOWN',
                'latency_ms' => $latest->latency_ms ?? 0,
                'uptime_24h' => SystemHealth::where('service_name', $service)
                    ->where('recorded_at', '>', now()->subHours(24))
                    ->selectRaw('count(case when status = "operational" then 1 end) * 100.0 / count(*) as uptime')
                    ->value('uptime') ?? 100.0,
                'last_check' => $latest->recorded_at ?? null
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'infrastructure' => [
                'region' => 'EQUATORIAL_NODE_V3',
                'p_node' => gethostbyname(gethostname()),
                'version' => 'STW-2026.Q1'
            ]
        ]);
    }
}
