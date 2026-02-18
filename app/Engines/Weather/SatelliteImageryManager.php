<?php

namespace App\Engines\Weather;

use Illuminate\Support\Facades\Log;

class SatelliteImageryManager
{
    public function __construct(
        protected HimawariService $himawari,
        protected NoaaGoesService $noaa,
        protected EumetsatService $eumetsat
    ) {
    }

    /**
     * Synchronize imagery for all major global meteorological constellations.
     */
    public function syncAll(): array
    {
        $results = [
            'asia_pacific' => [],
            'americas' => [],
            'emea' => []
        ];

        Log::info("Global Satellite Imagery Sync Initiated...");

        // 1. Asia-Pacific (Himawari)
        $results['asia_pacific']['41836'] = (bool) $this->himawari->downloadLatest(); // H-9
        // 40267 is Himawari-8, same service pattern could apply if needed

        // 2. Americas (GOES Network)
        $goesIds = ['60133', '41866', '43226', '53461']; // 19, 16, 17, 18
        foreach ($goesIds as $id) {
            $path = $this->noaa->downloadLatest($id);
            $results['americas'][$id] = $path ? true : false;
        }

        // 3. EMEA (Meteosat Network)
        $results['emea']['28912'] = (bool) $this->eumetsat->downloadLatest('28912'); // Met-9

        Log::info("Global Sync Complete.", $results);

        return $results;
    }
}
