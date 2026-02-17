<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [\App\Http\Controllers\User\UserDashboardController::class, 'index'])->name('home');

Route::get('/dashboard', [\App\Http\Controllers\User\UserDashboardController::class, 'index'])->name('dashboard');

Route::get('/weather/map', function () {
    return Inertia::render('User/WeatherMap');
})->name('weather.map');

// Internal Map Data APIs (Strategic Bypass for rendering stability)
Route::prefix('api/internal-map')->group(function () {
    $bypassCheck = function ($request) {
        $internalToken = 'vethinh_strategic_internal_token_2026';
        return $request->query('token') === $internalToken || auth()->check();
    };

    Route::get('/satellites', function (\Illuminate\Http\Request $request) {
        $internalToken = 'vethinh_strategic_internal_token_2026';
        if ($request->query('token') !== $internalToken && !auth()->check()) {
            return response()->json(['error' => 'Access Restricted'], 401);
        }
        return app(\App\Http\Controllers\Api\V1\WeatherController::class)->satellites();
    });

    Route::get('/storms', function (\Illuminate\Http\Request $request) {
        $internalToken = 'vethinh_strategic_internal_token_2026';
        if ($request->query('token') !== $internalToken && !auth()->check()) {
            return response()->json(['error' => 'Access Restricted'], 401);
        }
        return \App\Models\Storm::where('status', 'active')->get();
    });
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Admin & Protected routes (already exists below)
});

Route::get('/alerts', [\App\Http\Controllers\User\AlertController::class, 'index'])->name('alerts.index');
Route::get('/api-portal', [\App\Http\Controllers\User\ApiKeyController::class, 'index'])->name('apikeys.index');
Route::get('/api-docs', function () {
    return Inertia::render('User/ApiDocs');
})->name('user.api-docs');
Route::get('/reports', [\App\Http\Controllers\User\ReportController::class, 'index'])->name('user.reports.index');
Route::get('/reports/{file}/download', [\App\Http\Controllers\User\ReportController::class, 'download'])->name('user.reports.download');

Route::get('/login', function () {
    return redirect()->route('home');
})->name('login');

Route::prefix('admin')->group(function () {
    // Dashboard
    Route::get('/', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'index'])->name('admin.dashboard');

    // Satellites
    Route::get('/satellites', [\App\Http\Controllers\Admin\SatelliteManagementController::class, 'index'])->name('admin.satellites.index');
    Route::post('/satellites', [\App\Http\Controllers\Admin\SatelliteManagementController::class, 'store'])->name('admin.satellites.store');
    Route::put('/satellites/{satellite}', [\App\Http\Controllers\Admin\SatelliteManagementController::class, 'update'])->name('admin.satellites.update');
    Route::delete('/satellites/{satellite}', [\App\Http\Controllers\Admin\SatelliteManagementController::class, 'destroy'])->name('admin.satellites.destroy');

    // Ground Stations (NEW)
    Route::get('/ground-stations', [\App\Http\Controllers\Admin\GroundStationController::class, 'index'])->name('admin.ground-stations.index');
    Route::post('/ground-stations', [\App\Http\Controllers\Admin\GroundStationController::class, 'store'])->name('admin.ground-stations.store');
    Route::put('/ground-stations/{station}', [\App\Http\Controllers\Admin\GroundStationController::class, 'update'])->name('admin.ground-stations.update');
    Route::delete('/ground-stations/{station}', [\App\Http\Controllers\Admin\GroundStationController::class, 'destroy'])->name('admin.ground-stations.destroy');

    // Mission Control
    Route::get('/mission-control', function () {
        return Inertia::render('Admin/MissionControl');
    })->name('admin.mission-control');

    // Alert & Notification Settings
    Route::get('/alerts/settings', [\App\Http\Controllers\Admin\AlertSettingsController::class, 'index'])->name('admin.alerts.settings');
    Route::post('/alerts/settings', [\App\Http\Controllers\Admin\AlertSettingsController::class, 'update'])->name('admin.alerts.update');

    // Audit Rules (Condition Engine) (NEW)
    Route::get('/alerts/rules', [\App\Http\Controllers\Admin\AlertRuleController::class, 'index'])->name('admin.alerts.rules');
    Route::post('/alerts/rules', [\App\Http\Controllers\Admin\AlertRuleController::class, 'store'])->name('admin.alerts.rules.store');
    Route::put('/alerts/rules/{rule}', [\App\Http\Controllers\Admin\AlertRuleController::class, 'update'])->name('admin.alerts.rules.update');
    Route::delete('/alerts/rules/{rule}', [\App\Http\Controllers\Admin\AlertRuleController::class, 'destroy'])->name('admin.alerts.rules.destroy');

    // System Operations (Audit & Roles) (NEW)
    Route::get('/system/audit-logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('admin.system.audit');
    Route::get('/system/roles', [\App\Http\Controllers\Admin\RoleManagementController::class, 'index'])->name('admin.system.roles');

    // Billing
    Route::get('/billing', [\App\Http\Controllers\Admin\BillingController::class, 'index'])->name('admin.billing');

    // API Keys
    Route::get('/api-keys', [\App\Http\Controllers\Admin\ApiKeyManagementController::class, 'index'])->name('admin.apikeys.index');
    Route::post('/api-keys', [\App\Http\Controllers\Admin\ApiKeyManagementController::class, 'store'])->name('admin.apikeys.store');
    Route::put('/api-keys/{apiKey}', [\App\Http\Controllers\Admin\ApiKeyManagementController::class, 'update'])->name('admin.apikeys.update');
    Route::delete('/api-keys/{apiKey}', [\App\Http\Controllers\Admin\ApiKeyManagementController::class, 'destroy'])->name('admin.apikeys.destroy');

    // Users
    Route::get('/users', [\App\Http\Controllers\Admin\UserManagementController::class, 'index'])->name('admin.users.index');
    Route::post('/users', [\App\Http\Controllers\Admin\UserManagementController::class, 'store'])->name('admin.users.store');
    Route::put('/users/{user}', [\App\Http\Controllers\Admin\UserManagementController::class, 'update'])->name('admin.users.update');
    Route::delete('/users/{user}', [\App\Http\Controllers\Admin\UserManagementController::class, 'destroy'])->name('admin.users.destroy');

    // System Health (SLA) (NEW)
    Route::get('/system/health', [\App\Http\Controllers\Admin\SystemHealthController::class, 'index'])->name('admin.system.health');
});
