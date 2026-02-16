<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Dashboard');
});

Route::middleware(['auth'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'index'])->name('admin.dashboard');

    // Satellites
    Route::get('/satellites', [\App\Http\Controllers\Admin\SatelliteManagementController::class, 'index'])->name('admin.satellites.index');
    Route::post('/satellites', [\App\Http\Controllers\Admin\SatelliteManagementController::class, 'store'])->name('admin.satellites.store');
    Route::put('/satellites/{satellite}', [\App\Http\Controllers\Admin\SatelliteManagementController::class, 'update'])->name('admin.satellites.update');
    Route::delete('/satellites/{satellite}', [\App\Http\Controllers\Admin\SatelliteManagementController::class, 'destroy'])->name('admin.satellites.destroy');

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
});
