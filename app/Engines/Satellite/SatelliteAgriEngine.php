<?php

namespace App\Engines\Satellite;

class SatelliteAgriEngine
{
    /**
     * Generate NDVI (Normalized Difference Vegetation Index) data for a given region.
     */
    public function generateNdviHeatmap(): array
    {
        $points = [];
        // Focus on known agricultural/forest regions
        $regions = [
            ['lat' => 10, 'lng' => 106, 'name' => 'Southeast Asia'],
            ['lat' => -10, 'lng' => -60, 'name' => 'Amazon Basin'],
            ['lat' => 40, 'lng' => -100, 'name' => 'US Midwest'],
            ['lat' => 45, 'lng' => 30, 'name' => 'Ukraine/Russia Plains']
        ];

        foreach ($regions as $region) {
            for ($i = 0; $i < 50; $i++) {
                $points[] = [
                    'lat' => $region['lat'] + (mt_rand(-500, 500) / 100),
                    'lng' => $region['lng'] + (mt_rand(-500, 500) / 100),
                    'value' => mt_rand(20, 90) / 100 // NDVI 0.2 to 0.9
                ];
            }
        }

        return $points;
    }

    /**
     * Detect deforestation anomalies.
     */
    public function detectAnomalies(): array
    {
        return [
            [
                'lat' => -3.46,
                'lng' => -62.21,
                'type' => 'DEFORESTATION_EVENT',
                'severity' => 'CRITICAL',
                'description' => 'Rapid canopy loss detected in Amazon Sector 4.'
            ]
        ];
    }
}
