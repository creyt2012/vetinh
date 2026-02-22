<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SurveillanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed Vessels (Global Distribution)
        $regions = [
            ['lat' => [10, 20], 'lng' => [100, 110]], // Southeast Asia
            ['lat' => [30, 45], 'lng' => [-10, 10]], // Mediterranean
            ['lat' => [30, 50], 'lng' => [-80, -60]], // US East Coast
            ['lat' => [-35, -20], 'lng' => [110, 150]], // Australia
        ];

        $types = ['CONTAINER_SHIP', 'OIL_TANKER', 'CARGO_VESSEL', 'RESEARCH_UNIT', 'FISHING_TRAWLER'];
        for ($i = 0; $i < 100; $i++) {
            $region = $regions[array_rand($regions)];
            \App\Models\Vessel::create([
                'mmsi' => '244' . rand(100000, 999999),
                'name' => 'STAR_UNIT_' . ($i + 100),
                'type' => $types[array_rand($types)],
                'latitude' => rand($region['lat'][0] * 10, $region['lat'][1] * 10) / 10,
                'longitude' => rand($region['lng'][0] * 10, $region['lng'][1] * 10) / 10,
                'heading' => rand(0, 360),
                'speed' => rand(8, 28),
                'status' => 'UNDER_WAY'
            ]);
        }

        // 2. Seed Risk Areas (Strategic Hazards)
        $riskTypes = [
            ['name' => 'CYCLONE_ALPHA', 'type' => 'STORM', 'severity' => 'HIGH', 'lat' => 15.5, 'lng' => 110.2],
            ['name' => 'REGIONAL_FLOOD', 'type' => 'ENVIRONMENTAL', 'severity' => 'MEDIUM', 'lat' => 21.0, 'lng' => 105.8],
            ['name' => 'DEBRIS_CLOUD_K8', 'type' => 'ORBITAL', 'severity' => 'HIGH', 'lat' => -10.0, 'lng' => 45.0],
            ['name' => 'GULF_CONGESTION', 'type' => 'MARITIME', 'severity' => 'LOW', 'lat' => 25.0, 'lng' => 55.0],
        ];

        $tenant = \App\Models\Tenant::first();
        foreach ($riskTypes as $risk) {
            \App\Models\RiskArea::create([
                'tenant_id' => $tenant->id,
                'name' => $risk['name'],
                'type' => $risk['type'],
                'severity' => $risk['severity'],
                'geometry' => [
                    'type' => 'Polygon',
                    'coordinates' => [
                        [
                            [$risk['lng'] - 2, $risk['lat'] - 2],
                            [$risk['lng'] + 2, $risk['lat'] - 2],
                            [$risk['lng'] + 2, $risk['lat'] + 2],
                            [$risk['lng'] - 2, $risk['lat'] + 2],
                            [$risk['lng'] - 2, $risk['lat'] - 2],
                        ]
                    ]
                ],
                'is_active' => true
            ]);
        }

        // 3. Seed System Health for Dashboard (24h history)
        $services = ['Database', 'Redis', 'API Gateway', 'Reverb Cluster', 'Horizon Queue'];
        foreach ($services as $service) {
            for ($h = 0; $h < 24; $h++) {
                \App\Models\SystemHealth::create([
                    'service_name' => $service,
                    'status' => 'operational',
                    'latency_ms' => $service === 'API Gateway' ? rand(8, 25) : rand(1, 5),
                    'recorded_at' => now()->subHours($h)
                ]);
            }
        }

        // 3. Seed Conjunctions
        $sats = \App\Models\Satellite::limit(2)->get();
        if ($sats->count() >= 2) {
            \App\Models\Conjunction::create([
                'satellite_a_id' => $sats[0]->id,
                'satellite_b_id' => $sats[1]->id,
                'distance' => 0.45,
                'probability' => '1.2e-4',
                'tca' => now()->addHours(4),
                'status' => 'ACTIVE'
            ]);
        }
    }
}
