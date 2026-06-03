<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/vehicles/maintenance/{id}', [\App\Http\Controllers\MaintenanceController::class, 'show'])->name('vehicles.maintenance.details.page');
Route::get('/vehicles/maintenance/{id}/data', [\App\Http\Controllers\MaintenanceController::class, 'getMaintenanceData'])->name('vehicles.maintenance.data');

require __DIR__ . '/admin.php';
require __DIR__ . '/driver.php';
require __DIR__ . '/auth.php';
