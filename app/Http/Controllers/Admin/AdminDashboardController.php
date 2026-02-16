<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Satellite;
use App\Models\ApiKey;
use App\Models\User;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/AdminDashboard', [
            'stats' => [
                'total_satellites' => Satellite::count(),
                'active_satellites' => Satellite::where('status', 'ACTIVE')->count(),
                'total_keys' => ApiKey::count(),
                'active_keys' => ApiKey::where('is_active', true)->count(),
                'total_users' => User::count(),
                'monthly_usage' => ApiKey::sum('usage_count'),
            ],
            'recent_keys' => ApiKey::latest()->take(5)->get(),
        ]);
    }
}
