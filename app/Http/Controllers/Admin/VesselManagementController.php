<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vessel;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VesselManagementController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/VesselManagement', [
            'vessels' => Vessel::latest()->paginate(20)
        ]);
    }

    public function update(Request $request, Vessel $vessel)
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'metadata' => 'nullable|array'
        ]);

        $vessel->update($validated);

        return redirect()->back()->with('success', 'Vessel intelligence updated');
    }

    public function destroy(Vessel $vessel)
    {
        $vessel->delete();
        return redirect()->back()->with('success', 'Vessel removed from active surveillance');
    }
}
