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
        return Inertia::render('Admin/SatelliteManagement', [
            'satellites' => Satellite::orderBy('name')->get()
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
        ]);

        Satellite::create($validated);

        return redirect()->back()->with('success', 'Satellite added successfully');
    }

    public function update(Request $request, Satellite $satellite)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'type' => 'required|string',
            'status' => 'required|string',
            'tle_line1' => 'nullable|string',
            'tle_line2' => 'nullable|string',
        ]);

        $satellite->update($validated);

        return redirect()->back()->with('success', 'Satellite updated successfully');
    }

    public function destroy(Satellite $satellite)
    {
        $satellite->delete();
        return redirect()->back()->with('success', 'Satellite removed');
    }
}
