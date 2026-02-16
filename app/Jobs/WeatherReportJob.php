<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class WeatherReportJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $metrics = \App\Models\WeatherMetric::where('captured_at', '>', now()->subDay())->get();
        if ($metrics->isEmpty())
            return;

        $summary = [
            'period' => 'LAST_24_HOURS',
            'average_temp' => $metrics->avg('temperature'),
            'max_wind_speed' => $metrics->max('wind_speed'),
            'min_pressure' => $metrics->min('pressure'),
            'risk_level' => $metrics->max('risk_score') > 70 ? 'CRITICAL' : 'STABLE',
            'total_observations' => $metrics->count(),
            'extreme_events' => $metrics->where('risk_score', '>', 80)->count()
        ];

        // In a real staging, we would dispatch a PDF generation event here
        // For now, we store this as a system-generated mission file
        \App\Models\MissionFile::create([
            'tenant_id' => 1, // Default tenant for global reports
            'filename' => 'INTELLIGENCE_REPORT_' . now()->format('Y_m_d_H') . '.json', // Placeholder for PDF
            'type' => 'METEOROLOGICAL_REPORT',
            'status' => 'COMPLETED',
            'metadata' => $summary
        ]);
    }
}
