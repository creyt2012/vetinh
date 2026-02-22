<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WeatherMetric;
use App\Models\Satellite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StacController extends Controller
{
    /**
     * STAC Landing Page (Root Catalog)
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'stac_version' => '1.0.0',
            'type' => 'Catalog',
            'id' => 'deepsky-stac-v1',
            'title' => 'DeepSky SpatioTemporal Asset Catalog',
            'description' => 'Real-time and historical satellite imagery and weather metrics from DeepSky Constellation.',
            'links' => [
                ['rel' => 'self', 'type' => 'application/json', 'href' => url('/api/v1/stac')],
                ['rel' => 'root', 'type' => 'application/json', 'href' => url('/api/v1/stac')],
                ['rel' => 'collections', 'type' => 'application/json', 'href' => url('/api/v1/stac/collections')],
                ['rel' => 'search', 'type' => 'application/json', 'href' => url('/api/v1/stac/search'), 'method' => 'GET']
            ]
        ]);
    }

    /**
     * STAC Collections
     */
    public function collections(): JsonResponse
    {
        return response()->json([
            'collections' => [
                [
                    'id' => 'weather-metrics',
                    'title' => 'Global Weather Metrics',
                    'description' => 'In-situ and satellite-derived weather telemetry.',
                    'license' => 'proprietary',
                    'extent' => [
                        'spatial' => ['bbox' => [[-180, -90, 180, 90]]],
                        'temporal' => ['interval' => [[now()->subMonths(1)->toIso8601String(), null]]]
                    ],
                    'links' => [
                        ['rel' => 'self', 'type' => 'application/json', 'href' => url('/api/v1/stac/collections/weather-metrics')],
                        ['rel' => 'items', 'type' => 'application/json', 'href' => url('/api/v1/stac/collections/weather-metrics/items')]
                    ]
                ],
                [
                    'id' => 'satellite-imagery',
                    'title' => 'Active Satellite Imagery',
                    'description' => 'Raw and processed imagery from Himawari, GOES, and EUMETSAT.',
                    'license' => 'proprietary',
                    'extent' => [
                        'spatial' => ['bbox' => [[-180, -45, 180, 45]]],
                        'temporal' => ['interval' => [[now()->subDays(7)->toIso8601String(), null]]]
                    ],
                    'links' => [
                        ['rel' => 'self', 'type' => 'application/json', 'href' => url('/api/v1/stac/collections/satellite-imagery')],
                        ['rel' => 'items', 'type' => 'application/json', 'href' => url('/api/v1/stac/collections/satellite-imagery/items')]
                    ]
                ]
            ],
            'links' => [
                ['rel' => 'self', 'type' => 'application/json', 'href' => url('/api/v1/stac/collections')],
                ['rel' => 'parent', 'type' => 'application/json', 'href' => url('/api/v1/stac')]
            ]
        ]);
    }

    /**
     * STAC Item Search (Mock/Basic)
     */
    public function search(Request $request): JsonResponse
    {
        $bbox = $request->get('bbox'); // min_lon, min_lat, max_lon, max_lat
        $datetime = $request->get('datetime');

        // Simple filtering on WeatherMetric as items
        $query = WeatherMetric::query();

        if ($bbox) {
            $coords = explode(',', $bbox);
            if (count($coords) === 4) {
                $query->whereBetween('longitude', [$coords[0], $coords[2]])
                    ->whereBetween('latitude', [$coords[1], $coords[3]]);
            }
        }

        $items = $query->latest('captured_at')->limit(50)->get()->map(function ($m) {
            return [
                'type' => 'Feature',
                'stac_version' => '1.0.0',
                'id' => "item-{$m->id}",
                'collection' => 'weather-metrics',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [$m->longitude, $m->latitude]
                ],
                'properties' => [
                    'datetime' => $m->captured_at->toIso8601String(),
                    'temp' => $m->provenance['temp_derived'] ?? null,
                    'pressure' => $m->pressure
                ],
                'assets' => [
                    'thumbnail' => [
                        'href' => asset('storage/weather/' . ($m->provenance['image_id'] ?? 'latest.png')),
                        'type' => 'image/png'
                    ]
                ]
            ];
        });

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $items,
            'links' => [
                ['rel' => 'self', 'type' => 'application/json', 'href' => url('/api/v1/stac/search')]
            ]
        ]);
    }
}
