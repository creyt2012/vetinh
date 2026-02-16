<?php

namespace App\Jobs;

use App\Models\WeatherMetric;
use App\Services\Notifications\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AlertMonitorJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job to monitor system vitals and trigger alerts.
     */
    public function handle(
        NotificationService $notificationService,
        \App\Services\Alerting\ConditionEngine $conditionEngine
    ): void {
        // 1. Fetch Latest Metrics across all sectors
        $metrics = WeatherMetric::where('captured_at', '>', now()->subMinutes(10))->get();
        $rules = \App\Models\AlertRule::where('is_active', true)->get();

        foreach ($metrics as $metric) {
            // Prepare data for engine
            $currentState = [
                'temperature' => $metric->temperature,
                'pressure' => $metric->pressure,
                'wind_speed' => $metric->wind_speed,
                'rain_intensity' => $metric->rain_intensity ?? 0,
                'risk_score' => $metric->risk_score
            ];

            // Fetch previous state for trend analysis (e.g., 1 hour ago)
            $previous = WeatherMetric::where('latitude', $metric->latitude)
                ->where('longitude', $metric->longitude)
                ->where('captured_at', '<', $metric->captured_at)
                ->orderBy('captured_at', 'desc')
                ->first();

            $previousState = $previous ? [
                'temperature' => $previous->temperature,
                'pressure' => $previous->pressure,
                'wind_speed' => $previous->wind_speed,
                'rain_intensity' => $previous->rain_intensity ?? 0
            ] : null;

            // 2. Evaluate against Intelligence Rules
            $evaluation = $conditionEngine->evaluateModelRules($currentState, $previousState, $rules);

            if ($evaluation['triggered']) {
                $notificationService->broadcastAlert(
                    'INTELLIGENT_THREAT_DETECTION',
                    "{$evaluation['reason']} detected. Status: {$evaluation['level']}",
                    $evaluation['level']
                );

                // If the rule has specific channels, notify them directly
                if (!empty($evaluation['channels'])) {
                    $targets = [];
                    // This would need logic to map channel names to tenant config
                    // For now, it pulses through the general broadcast
                }
            }
        }
    }
}
