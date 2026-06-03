<?php

use App\Http\Controllers\FuelMileageController;

// Driver Fuel & Mileage Routes
Route::middleware(['auth', 'role:driver'])->prefix('driver/fuel-mileage')->name('driver.fuel-mileage.')->group(function () {
    Route::get('/dashboard', [FuelMileageController::class, 'dashboard'])->name('dashboard');
    
    // Maintenance Routes
    Route::get('/maintenance', [FuelMileageController::class, 'maintenanceLogs'])->name('maintenance.index');
    Route::get('/maintenance/create', [FuelMileageController::class, 'createMaintenanceRequest'])->name('maintenance.create');
    Route::post('/maintenance/store', [FuelMileageController::class, 'storeMaintenanceRequest'])->name('maintenance.store');
    Route::get('/maintenance/{id}', [FuelMileageController::class, 'showMaintenanceLog'])->name('maintenance.show');
    Route::delete('/maintenance/{id}/delete', [FuelMileageController::class, 'destroyMaintenanceLog'])->name('maintenance.destroy');
    
    // Mileage Routes
    Route::get('/mileage-logs', [FuelMileageController::class, 'mileageLogs'])->name('mileage-logs.index');
    Route::get('/mileage-logs/create', [FuelMileageController::class, 'createMileageLog'])->name('mileage-logs.create');
    Route::post('/mileage-logs/store', [FuelMileageController::class, 'storeMileageLog'])->name('mileage-logs.store');
    Route::get('/mileage-logs/{id}', [FuelMileageController::class, 'showMileageLog'])->name('mileage-logs.show');
    Route::delete('/mileage-logs/{id}', [FuelMileageController::class, 'destroyMileageLog'])->name('mileage-logs.destroy');
    
    // Reports & Quick Log
    Route::get('/reports', [FuelMileageController::class, 'reports'])->name('reports');
    Route::get('/quick-log', [FuelMileageController::class, 'quickLog'])->name('quick-log');
    Route::post('/quick-log/store', [FuelMileageController::class, 'storeQuickLog'])->name('quick-log.store');
});