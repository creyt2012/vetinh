<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;

class AlertController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();
        $alerts = ($user && $user->tenant)
            ? $user->tenant->activityLogs()->where('action', 'NOTIFICATION_SENT')->latest()->limit(50)->get()
            : [];

        return Inertia::render('User/Alerts/Index', [
            'alerts' => $alerts
        ]);
    }
}
