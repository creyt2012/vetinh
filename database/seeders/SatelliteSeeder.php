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
                'name' => 'STARLINK-31182',
                'norad_id' => '58345',
                'tle_line1' => '1 58345U 23174A   24047.38888889  .00064542  00000-0  52345-3 0  9990',
                'tle_line2' => '2 58345  53.0567 151.7821 0001456  87.3456 272.7654 15.08825381  1234',
                'type' => 'COMMUNICATION',
                'status' => 'ACTIVE'
            ],
            [
                'name' => 'GPS BIIR-2 (PRN 13)',
                'norad_id' => '24876',
                'tle_line1' => '1 24876U 97035A   24047.50000000  .00000054  00000-0  00000-0 0  9993',
                'tle_line2' => '2 24876  55.5678 201.2345 0123456 123.4567 234.5678  2.00567891 12345',
                'type' => 'NAVIGATION',
                'status' => 'ACTIVE'
            ],
            [
                'name' => 'SENTINEL-2A',
                'norad_id' => '40697',
                'tle_line1' => '1 40697U 15028A   24047.85966442  .00000140  00000-0  10487-3 0  9997',
                'tle_line2' => '2 40697  98.5663 118.9950 0001141  85.7483 274.3858 14.30825381452140',
                'type' => 'SCIENTIFIC',
                'status' => 'ACTIVE'
            ],
            [
                'name' => 'DEBRIS (PEGASUS R/B)',
                'norad_id' => '23106',
                'tle_line1' => '1 23106U 94029B   24047.45678912  .00001234  00000-0  12345-3 0  9995',
                'tle_line2' => '2 23106  81.2345 312.4567 0234567  12.3456 348.7654 14.56789123  1234',
                'type' => 'SPACE_DEBRIS',
                'status' => 'INACTIVE'
            ]
        ];

        foreach ($satellites as $sat) {
            Satellite::updateOrCreate(['norad_id' => $sat['norad_id']], $sat);
        }
    }
}
