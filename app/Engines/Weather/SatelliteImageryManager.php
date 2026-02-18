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
        $path = $this->himawari->downloadLatest();
        $results['asia_pacific']['41836'] = $path ? true : false;

        // 2. Americas (GOES-East & West)
        $path19 = $this->noaa->downloadLatest('60133'); // GOES-19
        $path16 = $this->noaa->downloadLatest('41866'); // GOES-16
        $results['americas']['60133'] = $path19 ? true : false;
        $results['americas']['41866'] = $path16 ? true : false;

        // 3. EMEA (Meteosat)
        $pathMet = $this->eumetsat->downloadLatest('28912'); // Meteosat-9
        $results['emea']['28912'] = $pathMet ? true : false;

        Log::info("Global Sync Complete.", $results);

        return $results;
    }
}
