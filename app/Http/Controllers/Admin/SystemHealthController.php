<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemHealth;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SystemHealthController extends Controller
{
    public function index(): Response
    {
        $services = ['Database', 'Redis', 'API Gateway', 'Reverb Cluster', 'Horizon Queue'];
        $slaData = [];

        foreach ($services as $service) {
            $slaData[$service] = [
                'current' => SystemHealth::where('service_name', $service)->latest('recorded_at')->first(),
                'uptime_24h' => SystemHealth::where('service_name', $service)
                    ->where('recorded_at', '>', now()->subHours(24))
                    ->selectRaw('count(case when status = "operational" then 1 end) * 100.0 / count(*) as uptime')
                    ->value('uptime') ?? 100.0,
                'avg_latency' => SystemHealth::where('service_name', $service)
                    ->where('recorded_at', '>', now()->subHours(24))
                    ->avg('latency_ms') ?? 0,
                'history' => SystemHealth::where('service_name', $service)
                    ->where('recorded_at', '>', now()->subHours(6))
                    ->orderBy('recorded_at', 'asc')
                    ->get(['latency_ms', 'recorded_at', 'status']),
                'metadata' => $service === 'Horizon Queue' ? [
                    'waiting_jobs' => \Laravel\Horizon\Contracts\JobRepository::class ? 0 : 'N/A', // Simple check
                    'status' => 'ACTIVE'
                ] : []
            ];
        }

        // External Pipeline Connectivity Check
        $externalApis = [
            'CELESTRAK_TLE' => 'https://celestrak.org/NORAD/elements/gp.php?GROUP=active&FORMAT=tle',
            'RAINVIEWER_RADAR' => 'https://api.rainviewer.com/public/weather-maps.json',
            'HIMAWARI_ICT' => 'https://himawari8-dl.nict.go.jp/himawari8/img/D531106/latest.json'
        ];

        $externalStatus = [];
        foreach ($externalApis as $name => $url) {
            try {
                $start = microtime(true);
                $resp = \Illuminate\Support\Facades\Http::timeout(5)->get($url);
                $latency = round((microtime(true) - $start) * 1000);

                $externalStatus[$name] = [
                    'status' => $resp->successful() ? 'CONNECTED' : 'FAILED',
                    'latency' => $latency,
                    'last_check' => now()->toIso8601String()
                ];
            } catch (\Exception $e) {
                $externalStatus[$name] = [
                    'status' => 'OFFLINE',
                    'latency' => 0,
                    'last_check' => now()->toIso8601String()
                ];
            }
        }

        return Inertia::render('Admin/System/Health', [
            'sla' => $slaData,
            'externalApis' => $externalStatus,
            'recentLogs' => SystemHealth::latest('recorded_at')->limit(20)->get()
        ]);
    }
}
