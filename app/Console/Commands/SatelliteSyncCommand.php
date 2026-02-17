<?php

namespace App\Console\Commands;

use App\Engines\Satellite\CelesTrakService;
use App\Models\Satellite;
use Illuminate\Console\Command;

class SatelliteSyncCommand extends Command
{
    protected $signature = 'satellite:sync';
    protected $description = 'Sync real-time TLE and metadata for global meteorological satellites';

    private const STRATEGIC_WEATHER_SATS = [
        '60133' => ['name' => 'GOES-19 (EAST)', 'type' => 'METEOROLOGICAL', 'org' => 'NOAA'],
        '43226' => ['name' => 'GOES-17 (WEST)', 'type' => 'METEOROLOGICAL', 'org' => 'NOAA'],
        '41866' => ['name' => 'GOES-16 (EAST)', 'type' => 'METEOROLOGICAL', 'org' => 'NOAA'],
        '41836' => ['name' => 'HIMAWARI-9', 'type' => 'METEOROLOGICAL', 'org' => 'JMA'],
        '40267' => ['name' => 'HIMAWARI-8', 'type' => 'METEOROLOGICAL', 'org' => 'JMA'],
        '43689' => ['name' => 'METOP-C', 'type' => 'METEOROLOGICAL', 'org' => 'EUMETSAT'],
        '38771' => ['name' => 'METOP-B', 'type' => 'METEOROLOGICAL', 'org' => 'EUMETSAT'],
        '54234' => ['name' => 'NOAA-21 (JPSS-2)', 'type' => 'METEOROLOGICAL', 'org' => 'NOAA'],
        '43013' => ['name' => 'NOAA-20 (JPSS-1)', 'type' => 'METEOROLOGICAL', 'org' => 'NOAA'],
        '33591' => ['name' => 'NOAA-19', 'type' => 'METEOROLOGICAL', 'org' => 'NOAA'],
        '28912' => ['name' => 'METEOSAT-9', 'type' => 'METEOROLOGICAL', 'org' => 'EUMETSAT'],
        '25544' => ['name' => 'ISS (ZARYA)', 'type' => 'STATION', 'org' => 'NASA/ROSCOSMOS'],
        '57490' => ['name' => 'FENGYUN-3F', 'type' => 'METEOROLOGICAL', 'org' => 'CMA'],
        '43010' => ['name' => 'FENGYUN-3D', 'type' => 'METEOROLOGICAL', 'org' => 'CMA'],
    ];

    public function handle(CelesTrakService $celesTrak)
    {
        $this->info("Initiating Global Meteorological Satellite Synchronization...");

        // 1. Sync Strategic Assets first
        foreach (self::STRATEGIC_WEATHER_SATS as $noradId => $meta) {
            $this->info("Processing Strategic Asset: {$meta['name']} (NORAD: {$noradId})");
            $tle = $celesTrak->getSpecificTle($noradId);

            if ($tle) {
                Satellite::updateOrCreate(
                    ['norad_id' => $noradId],
                    [
                        'name' => $meta['name'],
                        'tle_line1' => $tle['tle_line1'],
                        'tle_line2' => $tle['tle_line2'],
                        'status' => 'ACTIVE',
                        'data_source' => 'CELESTRAK',
                        'source_url' => 'https://celestrak.org',
                        'type' => $meta['type'],
                        'api_config' => [
                            'organization' => $meta['org'],
                            'data_portal' => $this->resolveDataPortal($meta['org']),
                            'is_strategic' => true
                        ]
                    ]
                );
            }
        }

        // 2. Sync broader groups for general tracking
        $groups = ['WEATHER', 'STATIONS'];
        foreach ($groups as $group) {
            $this->info("Fetching broader group: {$group}");
            $satellites = $celesTrak->fetchGroup($group);

            foreach ($satellites as $noradId => $data) {
                // Don't overwrite strategic metadata if already processed
                if (isset(self::STRATEGIC_WEATHER_SATS[$noradId]))
                    continue;

                Satellite::updateOrCreate(
                    ['norad_id' => $noradId],
                    [
                        'name' => $data['name'],
                        'tle_line1' => $data['tle_line1'],
                        'tle_line2' => $data['tle_line2'],
                        'status' => 'ACTIVE',
                        'data_source' => 'CELESTRAK',
                        'type' => $group === 'WEATHER' ? 'METEOROLOGICAL' : 'STATION'
                    ]
                );
            }
        }

        $this->info("Full Synchronization Complete. Global Weather Network is Online.");
    }

    private function resolveDataPortal(string $org): string
    {
        return match ($org) {
            'NOAA' => 'https://www.noaa.gov/satellite-data',
            'EUMETSAT' => 'https://data.eumetsat.int',
            'JMA' => 'https://www.jma.go.jp/jma/jma-eng/satellite/',
            'CMA' => 'http://www.nsmc.org.cn/en/NSMC/Home/Index.html',
            default => 'https://celestrak.org'
        };
    }
}
