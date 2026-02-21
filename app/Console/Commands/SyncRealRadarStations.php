<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\RadarStation;
use Illuminate\Support\Facades\DB;

class SyncRealRadarStations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'radar:sync-global';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync global weather radar stations from RainViewer open database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fetching real global radar database from RainViewer...');

        $url = 'https://raw.githubusercontent.com/rainviewer/weather-radar-database/master/weather-radar-database.json';

        try {
            $response = Http::timeout(30)->get($url);

            if (!$response->successful()) {
                $this->error('Failed to fetch radar database: HTTP ' . $response->status());
                return 1;
            }

            $radars = $response->json();

            if (!is_array($radars)) {
                $this->error('Invalid JSON format received.');
                return 1;
            }

            $this->info('Received ' . count($radars) . ' radar entries. Synchronizing with local database...');

            $bar = $this->output->createProgressBar(count($radars));
            $bar->start();

            DB::beginTransaction();

            // Clear old mock data if needed (optional, or just rely on unique constraints and updates)
            // But let's keep it clean since user said they want REAL trạm, not mock.
            RadarStation::truncate();

            foreach ($radars as $radarId => $data) {
                // Ensure required fields exist
                if (!isset($data['latitude']) || !isset($data['longitude'])) {
                    $bar->advance();
                    continue;
                }

                // Determine name
                $name = $data['location'] ?? 'Unknown Location';
                if (isset($data['country'])) {
                    $name .= ', ' . $data['country'];
                }

                // Determine code
                $code = null;
                if (isset($data['codes'])) {
                    $code = $data['codes']['wmo'] ?? $data['codes']['icao'] ?? $data['codes']['national'] ?? null;
                }
                if (!$code) {
                    $code = substr($radarId, 0, 10); // Fallback to a truncated ID
                }

                $status = (isset($data['status']) && $data['status'] == 1) ? 'operational' : 'offline';

                // Frequency band
                $band = $data['antenna']['band'] ?? 'Unknown';
                if ($band === 'S' || $band === 'C' || $band === 'X') {
                    $band = $band . '-band';
                }

                RadarStation::create([
                    'name' => mb_substr($name, 0, 255),
                    'code' => mb_substr($code, 0, 50),
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'elevation_m' => $data['station']['height'] ?? $data['antenna']['height'] ?? 0,
                    'frequency_band' => mb_substr($band, 0, 50),
                    'coverage_radius_km' => $data['max_range'] ?? 250,
                    'status' => $status,
                    'parameters' => [
                        'radar_id' => $radarId,
                        'antenna' => $data['antenna'] ?? null,
                        'wrwp' => $data['wrwp'] ?? null,
                        'state' => $data['state'] ?? null
                    ]
                ]);

                $bar->advance();
            }

            DB::commit();
            $bar->finish();
            $this->newLine();
            $this->info('Successfully synchronized real radar stations!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error during synchronization: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
