<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Dashboard');
});

Route::prefix('admin')->group(function () {
    Route::get('/satellites', [\App\Http\Controllers\Admin\SatelliteManagementController::class, 'index'])->name('admin.satellites.index');
    Route::post('/satellites', [\App\Http\Controllers\Admin\SatelliteManagementController::class, 'store'])->name('admin.satellites.store');
    Route::put('/satellites/{satellite}', [\App\Http\Controllers\Admin\SatelliteManagementController::class, 'update'])->name('admin.satellites.update');
    Route::delete('/satellites/{satellite}', [\App\Http\Controllers\Admin\SatelliteManagementController::class, 'destroy'])->name('admin.satellites.destroy');
});
