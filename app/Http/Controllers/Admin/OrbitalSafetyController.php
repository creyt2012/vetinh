<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conjunction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrbitalSafetyController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/OrbitalSafetyManagement', [
            'conjunctions' => Conjunction::with(['satelliteA', 'satelliteB'])->latest()->get()
        ]);
    }

    public function update(Request $request, Conjunction $conjunction)
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'risk_level' => 'nullable|string'
        ]);

        $conjunction->update($validated);

        return redirect()->back()->with('success', 'Conjunction risk status updated');
    }
}
