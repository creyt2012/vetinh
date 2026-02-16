<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GroundStationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stations = [
            ['name' => 'Hanoi Meteo Hub', 'code' => 'HAN-MET-01', 'latitude' => 21.0285, 'longitude' => 105.8542, 'type' => 'METEO'],
            ['name' => 'Da Nang Coastal Radar', 'code' => 'DAD-RAD-02', 'latitude' => 16.0544, 'longitude' => 108.2022, 'type' => 'RADAR'],
            ['name' => 'SGN Mission Control', 'code' => 'SGN-MC-01', 'latitude' => 10.8231, 'longitude' => 106.6297, 'type' => 'MISSION_CONTROL'],
            ['name' => 'Kourou Spaceport', 'code' => 'GUY-SPACE-01', 'latitude' => 5.236, 'longitude' => -52.768, 'type' => 'MISSION_CONTROL'],
            ['name' => 'Svalbard Ground Station', 'code' => 'SVA-GND-01', 'latitude' => 78.2298, 'longitude' => 15.6469, 'type' => 'METEO'],
        ];

        foreach ($stations as $station) {
            \App\Models\GroundStation::updateOrCreate(['code' => $station['code']], $station);
        }
    }
}
