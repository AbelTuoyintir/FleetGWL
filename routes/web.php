<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/vehicles/maintenance/{id}', [\App\Http\Controllers\MaintenanceController::class, 'show'])->name('vehicles.maintenance.details.page');
Route::get('/vehicles/maintenance/{id}/data', [\App\Http\Controllers\MaintenanceController::class, 'getMaintenanceData'])->name('vehicles.maintenance.data');

// Maintenance alerts UI page (HTML)
Route::get('/maintenance/vehicles-needing', [\App\Http\Controllers\MaintenanceController::class, 'vehiclesNeedingPage'])->name('maintenance.vehicles-needing.page');

// Maintenance alerts data endpoint (JSON)
Route::get('/maintenance/vehicles-needing/data', [\App\Http\Controllers\MaintenanceController::class, 'getVehiclesNeedingMaintenance'])->name('maintenance.vehicles-needing.data');
require __DIR__ . '/admin.php';
require __DIR__ . '/driver.php';
require __DIR__ . '/auth.php';
