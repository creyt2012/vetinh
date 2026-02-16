<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\Log;

class QAQCProcessor
{
    /**
     * Validate and clean weather data.
     */
    public function process(array $data): array
    {
        $quality = 100;
        $flags = [];

        // 1. Physical Range Checks
        if ($data['temperature'] < -80 || $data['temperature'] > 60) {
            $quality -= 40;
            $flags[] = 'TEMP_OUT_OF_RANGE';
        }

        if ($data['humidity'] < 0 || $data['humidity'] > 100) {
            $quality -= 30;
            $flags[] = 'HUMIDITY_INVALID';
        }

        if ($data['pressure'] < 800 || $data['pressure'] > 1100) {
            $quality -= 30;
            $flags[] = 'PRESSURE_ANOMALY';
        }

        // 2. Logic Check (e.g., Rain without high humidity)
        if ($data['rain_intensity'] > 0 && $data['humidity'] < 30) {
            $quality -= 20;
            $flags[] = 'RAIN_HUMIDITY_MISMATCH';
        }

        return array_merge($data, [
            'quality_score' => max(0, $quality),
            'qa_flags' => $flags,
            'is_valid' => $quality >= 60
        ]);
    }
}
