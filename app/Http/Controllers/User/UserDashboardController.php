<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\WeatherMetric;
use App\Models\Satellite;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserDashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('User/Dashboard', [
            'metrics' => WeatherMetric::latest()->limit(10)->get(),
            'satellites' => Satellite::all(),
            'storms' => \App\Models\Storm::where('status', 'active')->get()
        ]);
    }
}
