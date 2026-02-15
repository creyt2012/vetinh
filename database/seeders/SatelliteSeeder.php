<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Satellite;

class SatelliteSeeder extends Seeder
{
    public function run(): void
    {
        $satellites = [
            [
                'name' => 'ISS (ZARYA)',
                'norad_id' => '25544',
                'tle_line1' => '1 25544U 98067A   24047.55160407  .00015560  00000-0  28362-3 0  9997',
                'tle_line2' => '2 25544  51.6416  21.9364 0004523  65.5721  54.5126 15.49250564439403',
                'type' => 'STATION',
                'status' => 'ACTIVE'
            ],
            [
                'name' => 'HIMAWARI 9',
                'norad_id' => '41836',
                'tle_line1' => '1 41836U 16064A   24047.16615598 -.00000302  00000-0  00000-0 0  9991',
                'tle_line2' => '2 41836   0.0152 141.6708 0001471 278.4728 112.5714  1.00271578 26685',
                'type' => 'WEATHER',
                'status' => 'ACTIVE'
            ],
            [
                'name' => 'SENTINEL-2A',
                'norad_id' => '40697',
                'tle_line1' => '1 40697U 15028A   24047.85966442  .00000140  00000-0  10487-3 0  9997',
                'tle_line2' => '2 40697  98.5663 118.9950 0001141  85.7483 274.3858 14.30825381452140',
                'type' => 'OBSERVATION',
                'status' => 'ACTIVE'
            ]
        ];

        foreach ($satellites as $sat) {
            Satellite::updateOrCreate(['norad_id' => $sat['norad_id']], $sat);
        }
    }
}
