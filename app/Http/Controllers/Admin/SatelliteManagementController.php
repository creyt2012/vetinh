<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Satellite;
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

        Satellite::create($validated);

        return redirect()->back()->with('success', 'Satellite mission config initialized');
    }

    public function update(Request $request, Satellite $satellite)
    {
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

        return redirect()->back()->with('success', 'Satellite configuration updated');
    }

    public function destroy(Satellite $satellite)
    {
        $satellite->delete();
        return redirect()->back()->with('success', 'Satellite removed');
    }
}
