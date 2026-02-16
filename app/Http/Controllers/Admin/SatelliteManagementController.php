<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Satellite;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SatelliteManagementController extends Controller
{
    public function index()
    {
        $satellites = Satellite::latest()->get()->map(function ($sat) {
            // Add mock telemetry for the advanced UI
            $sat->telemetry = [
                'altitude' => rand(350, 420) . ' km',
                'velocity' => (27000 + rand(0, 500)) . ' km/h',
                'signal' => rand(85, 99) . '%',
                'temp' => rand(15, 25) . '°C',
                'sensors' => [
                    ['name' => 'Optical_Sensor_v4', 'status' => 'ONLINE'],
                    ['name' => 'SAR_Imaging_Unit', 'status' => 'STANDBY'],
                    ['name' => 'IR_Radiometer', 'status' => 'ONLINE'],
                ]
            ];
            return $sat;
        });

        return Inertia::render('Admin/SatelliteManagement', [
            'satellites' => $satellites
        ]);
    }

    public function store(Request $request)
    {
        if (!$request->user()?->isOperator()) {
            abort(403, 'Unauthorized mission deployment');
        }

        $validated = $request->validate([
            'name' => 'required|string',
            'norad_id' => 'required|string|unique:satellites,norad_id',
            'type' => 'required|string',
            'tle_line1' => 'nullable|string',
            'tle_line2' => 'nullable|string',
            'status' => 'required|string',
            'api_config' => 'nullable|array',
            'data_source' => 'nullable|string',
            'source_url' => 'nullable|url',
            'dataset_name' => 'nullable|string',
            'priority' => 'nullable|integer',
        ]);

        $satellite = Satellite::create($validated);

        ActivityLog::log('SATELLITE_DEPLOYED', $satellite, $validated);

        return redirect()->back()->with('success', 'Satellite mission config initialized');
    }

    public function update(Request $request, Satellite $satellite)
    {
        if (!$request->user()?->isOperator()) {
            abort(403, 'Unauthorized mission reconfiguration');
        }

        $validated = $request->validate([
            'name' => 'required|string',
            'type' => 'required|string',
            'status' => 'required|string',
            'tle_line1' => 'nullable|string',
            'tle_line2' => 'nullable|string',
            'api_config' => 'nullable|array',
            'data_source' => 'nullable|string',
            'source_url' => 'nullable|url',
            'dataset_name' => 'nullable|string',
            'priority' => 'nullable|integer',
        ]);

        $satellite->update($validated);

        ActivityLog::log('SATELLITE_RECONFIGURED', $satellite, $validated);

        return redirect()->back()->with('success', 'Satellite configuration updated');
    }

    public function destroy(Request $request, Satellite $satellite)
    {
        if (!$request->user()?->isAdmin()) {
            abort(403, 'Unauthorized asset deactivation');
        }

        $satData = $satellite->toArray();
        $satellite->delete();

        ActivityLog::log('SATELLITE_DEACTIVATED', null, ['deleted_id' => $satData['id'], 'name' => $satData['name']]);

        return redirect()->back()->with('success', 'Satellite removed');
    }
}
