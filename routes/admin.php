<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\Admin\MileageLogController;
use App\Http\Controllers\FuelManagementController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\DocumentController;
Use App\Http\Controllers\LocationController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\VehicleTrackingController;

Route::middleware(['auth'])->prefix('vehicles')->name('vehicles.')->group(function () {
    Route::get('/', [VehicleController::class, 'index'])->name('index');
    Route::post('/', [VehicleController::class, 'store'])->name('store');
    Route::get('/data', [VehicleController::class, 'getVehiclesData'])->name('data');
    Route::get('/statistics', [VehicleController::class, 'getVehicleStatistics'])->name('statistics');
    Route::get('/form-data', [VehicleController::class, 'getFormData'])->name('form-data');
    Route::get('/export', [VehicleController::class, 'exportVehicles'])->name('export');

    Route::get('/import', [VehicleController::class, 'showImportForm'])->name('import.form');
    Route::post('/import', [VehicleController::class, 'importVehicles'])->name('import');
    Route::get('/template/download', [VehicleController::class, 'downloadTemplate'])->name('template');
    Route::get('/import/status', [VehicleController::class, 'getImportStatus'])->name('import.status');
    Route::get('/import/failed/download', [VehicleController::class, 'downloadFailedImportRows'])->name('import.failed.download');

    // Documents
    Route::prefix('documents')->name('documents.')->group(function () {
        // Route::get('/', [\App\Http\Controllers\DocumentController::class, 'index'])->name('index'); 
        Route::get('/trashed', [\App\Http\Controllers\DocumentController::class, 'trashed'])->name('trashed');
        Route::get('/expiring', [\App\Http\Controllers\DocumentController::class, 'expiringSoon'])->name('expiring');
        Route::get('/statistics', [\App\Http\Controllers\DocumentController::class, 'statistics'])->name('statistics');
        Route::post('/bulk-action', [\App\Http\Controllers\DocumentController::class, 'bulkAction'])->name('bulk-action');
        
        Route::get('/{document}/download', [\App\Http\Controllers\DocumentController::class, 'download'])->name('download');
        Route::get('/{document}/preview', [\App\Http\Controllers\DocumentController::class, 'preview'])->name('preview');
        Route::post('/{document}/acknowledge', [\App\Http\Controllers\DocumentController::class, 'acknowledge'])->name('acknowledge');
        Route::post('/{id}/restore', [\App\Http\Controllers\DocumentController::class, 'restore'])->name('restore');
        Route::delete('/{id}/force', [\App\Http\Controllers\DocumentController::class, 'forceDestroy'])->name('force-destroy');
    });
    Route::resource('documents', \App\Http\Controllers\DocumentController::class)->except(['destroy']);
    Route::delete('/documents/{document}', [\App\Http\Controllers\DocumentController::class, 'destroy'])->name('documents.destroy');

    Route::get('/{id}/edit', [VehicleController::class, 'edit'])->name('edit');
    Route::post('/{vehicle}/maintenance', [VehicleController::class, 'dispatchForMaintenance'])->name('maintenance');
    Route::post('/{vehicle}/maintenance/release', [VehicleController::class, 'releaseFromMaintenance'])->name('maintenance.release');
    Route::put('/{vehicle}', [VehicleController::class, 'update'])->name('update');
    Route::delete('/{vehicle}', [VehicleController::class, 'destroy'])->name('destroy');
    Route::get('/{id}', [VehicleController::class, 'show'])->name('show');
    Route::get('/{vehicle}/create-job-order', [VehicleController::class, 'createJobOrder'])->name('maintenance.job-order.create');
    Route::post('/{vehicle}/store-job-order', [VehicleController::class, 'storeJobOrder'])->name('maintenance.job-order.store');
    Route::get('/{vehicle}/job-orders', [VehicleController::class, 'jobOrders'])->name('maintenance.job-orders');
    Route::put('/{vehicle}/job-orders/{maintenance}/status', [VehicleController::class, 'updateJobOrderStatus'])->name('maintenance.job-order.status');
    Route::get('/vehicles/maintenance/{id}/data', [VehicleController::class, 'getMaintenanceData'])->name('vehicles.maintenance.data');
    Route::get('/vehicles/maintenance/{id}/details', [VehicleController::class, 'maintenanceDetailsPage'])->name('vehicles.maintenance.details.page');

    // Vehicle Tracking
    Route::get('/tracking', [VehicleTrackingController::class, 'index'])->name('tracking');
    Route::get('/tracking/data', [VehicleTrackingController::class, 'getVehiclesLocations'])->name('tracking.data');

    // Mileage Log Routes
    Route::get('/{id}/mileage-log', [VehicleController::class, 'getMileageLog'])->name('mileage.log');
    Route::post('/mileage/store', [VehicleController::class, 'storeMileage'])->name('mileage.store');
    Route::delete('/mileage/{id}', [VehicleController::class, 'deleteMileage'])->name('mileage.delete');

    // Fuel Log Routes
    Route::get('/{id}/fuel-log', [VehicleController::class, 'getFuelLog'])->name('fuel.log');
    Route::post('/fuel/store', [VehicleController::class, 'storeFuel'])->name('fuel.store');
    Route::delete('/fuel/{id}', [VehicleController::class, 'deleteFuel'])->name('fuel.delete');
    Route::get('/{id}/fuel/previous-odometer', [VehicleController::class, 'getPreviousOdometer'])->name('fuel.previous');
   
});

Route::middleware(['auth'])->prefix('fuel-management')->name('fuel-management.')->group(function () {
    Route::get('/', [FuelManagementController::class, 'index'])->name('index');
    Route::post('/', [FuelManagementController::class, 'store'])->name('store');
    Route::get('/quick-stats', [FuelManagementController::class, 'quickStats'])->name('quick-stats');
    Route::get('/analytics-data', [FuelManagementController::class, 'analyticsData'])->name('analytics-data');
    Route::get('/export', [FuelManagementController::class, 'export'])->name('export');
    Route::get('/vehicle-by-plate', [FuelManagementController::class, 'vehicleByPlate'])->name('vehicle-by-plate');
    Route::get('/cost-analysis', [FuelManagementController::class, 'costAnalysis'])->name('cost-analysis');
    Route::get('/cost-analysis-data', [FuelManagementController::class, 'getCostAnalysisData'])->name('cost-analysis-data');
    Route::get('/cost-breakdown', [FuelManagementController::class, 'getCostBreakdown'])->name('cost-breakdown');
    Route::get('/{fuelLog}/edit', [FuelManagementController::class, 'edit'])->name('edit');
    Route::get('/{id}/edit-data', [FuelManagementController::class, 'getEditData'])->name('edit-data');
    Route::post('/store', [FuelManagementController::class, 'store'])->name('store.post');
    Route::put('/{fuelLog}', [FuelManagementController::class, 'update'])->name('update');
    Route::delete('/{fuelLog}', [FuelManagementController::class, 'destroy'])->name('destroy');
});

// Search API routes
Route::middleware(['auth'])->group(function () {
    Route::get('/api/vehicles/search', [VehicleController::class, 'searchByRegistrationNumber'])->name('vehicles.search');
    Route::get('/api/vehicles/details/{id?}', [VehicleController::class, 'getVehicleDetails'])->name('vehicles.details');
    Route::get('/api/drivers/search', [DriverController::class, 'searchDrivers'])->name('drivers.search');
});

// Mileage Logs Routes
Route::middleware(['auth'])->prefix('mileage-logs')->name('mileage-logs.')->group(function () {
    Route::get('/', [MileageLogController::class, 'index'])->name('index');
    Route::get('/data', [MileageLogController::class, 'getData'])->name('data');
    Route::get('/statistics', [MileageLogController::class, 'getStatistics'])->name('statistics');
    Route::post('/store', [MileageLogController::class, 'store'])->name('store');
    Route::get('/{id}/edit-data', [MileageLogController::class, 'getEditData'])->name('edit-data');
    Route::get('/{id}', [MileageLogController::class, 'show'])->name('show');
    Route::put('/{id}', [MileageLogController::class, 'update'])->name('update');
    Route::delete('/{id}', [MileageLogController::class, 'destroy'])->name('destroy');
    Route::get('/export', [MileageLogController::class, 'export'])->name('export');
    Route::get('/analytics', [MileageLogController::class, 'analytics'])->name('analytics');
});

// Driver Management Routes
Route::middleware(['auth'])->prefix('drivers')->name('drivers.')->group(function () {
    Route::get('/', [DriverController::class, 'index'])->name('index');
    Route::get('/create', [DriverController::class, 'create'])->name('create');
    Route::post('/store', [DriverController::class, 'store'])->name('store');
    Route::get('/statistics', [DriverController::class, 'statistics'])->name('statistics');
    Route::get('/search', [DriverController::class, 'searchDrivers'])->name('search');
    Route::get('/{driver}', [DriverController::class, 'show'])->name('show');
    Route::get('/{driver}/edit', [DriverController::class, 'edit'])->name('edit');
    Route::put('/{driver}', [DriverController::class, 'update'])->name('update');
    Route::delete('/{driver}', [DriverController::class, 'destroy'])->name('destroy');
    Route::post('/{driver}/assign-vehicle', [DriverController::class, 'assignVehicle'])->name('assign-vehicle');
    Route::post('/{driver}/unassign-vehicle', [DriverController::class, 'unassignVehicle'])->name('unassign-vehicle');
});


Route::middleware(['auth'])->prefix('locations')->name('locations.')->group(function () {
    Route::get('/', [LocationController::class, 'index'])->name('index');
    Route::get('/stats', [LocationController::class, 'getStats'])->name('stats');
    
    // Regions
    Route::get('/regions/data', [LocationController::class, 'getRegions'])->name('regions.data');
    Route::get('/regions/list', [LocationController::class, 'getRegionsList'])->name('regions.list');
    Route::get('/regions/{id}', [LocationController::class, 'getRegion'])->name('regions.get');
    Route::get('/regions/{id}/districts', [LocationController::class, 'getDistrictsByRegion'])->name('regions.districts');
    Route::post('/regions/store', [LocationController::class, 'storeRegion'])->name('regions.store');
    Route::put('/regions/{id}', [LocationController::class, 'updateRegion'])->name('regions.update');
    Route::delete('/regions/{id}', [LocationController::class, 'deleteRegion'])->name('regions.delete');
    
    // Districts
    Route::get('/districts/data', [LocationController::class, 'getDistricts'])->name('districts.data');
    Route::get('/districts/{id}', [LocationController::class, 'getDistrict'])->name('districts.get');
    Route::post('/districts/store', [LocationController::class, 'storeDistrict'])->name('districts.store');
    Route::put('/districts/{id}', [LocationController::class, 'updateDistrict'])->name('districts.update');
    Route::delete('/districts/{id}', [LocationController::class, 'deleteDistrict'])->name('districts.delete');
    
    // Stations
    Route::get('/stations/data', [LocationController::class, 'getStations'])->name('stations.data');
    Route::get('/stations/{id}', [LocationController::class, 'getStation'])->name('stations.get');
    Route::post('/stations/store', [LocationController::class, 'storeStation'])->name('stations.store');
    Route::put('/stations/{id}', [LocationController::class, 'updateStation'])->name('stations.update');
    Route::delete('/stations/{id}', [LocationController::class, 'deleteStation'])->name('stations.delete');
});

Route::middleware(['auth'])->prefix('documents')->name('documents.')->group(function () {
    Route::get('/trashed', [DocumentController::class, 'trashed'])->name('trashed');
    Route::get('/expiring', [DocumentController::class, 'expiringSoon'])->name('expiring');
    Route::get('/statistics', [DocumentController::class, 'statistics'])->name('statistics');
    Route::post('/bulk-action', [DocumentController::class, 'bulkAction'])->name('bulk-action');

    Route::get('/{document}/download', [DocumentController::class, 'download'])->name('download');
    Route::get('/{document}/preview', [DocumentController::class, 'preview'])->name('preview');
    Route::post('/{document}/acknowledge', [DocumentController::class, 'acknowledge'])->name('acknowledge');
    Route::post('/{id}/restore', [DocumentController::class, 'restore'])->name('restore');
    Route::delete('/{id}/force', [DocumentController::class, 'forceDestroy'])->name('force-destroy');
});
Route::middleware(['auth'])->resource('documents', DocumentController::class)->except(['destroy']);
Route::middleware(['auth'])->delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

Route::middleware(['auth'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/utilization', function () {
        return redirect('/vehicles?tab=status-overview');
    })->name('utilization');

    Route::get('/cost', function () {
        return redirect('/fuel-management?tab=cost-analysis');
    })->name('cost');

    Route::get('/fuel-efficiency', function () {
        return redirect('/fuel-management?tab=analytics');
    })->name('fuel-efficiency');
});


// Maintenance Alert Routes
Route::middleware(['auth'])->prefix('maintenance')->name('maintenance.')->group(function () {
    Route::get('/vehicles-needing', [\App\Http\Controllers\MaintenanceController::class, 'vehiclesNeedingPage'])->name('vehicles-needing');
    Route::get('/vehicles-needing/data', [\App\Http\Controllers\MaintenanceController::class, 'getVehiclesNeedingMaintenance'])->name('vehicles-needing.data');
    Route::post('/vehicle/{id}/acknowledge', [\App\Http\Controllers\MaintenanceController::class, 'acknowledgeAlert'])->name('acknowledge');
    Route::get('/schedule/{vehicleId}', [\App\Http\Controllers\MaintenanceController::class, 'create'])->name('schedule');
    Route::get('/', [\App\Http\Controllers\MaintenanceController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\MaintenanceController::class, 'create'])->name('create');
    Route::post('/store', [\App\Http\Controllers\MaintenanceController::class, 'store'])->name('store');
    Route::get('/{id}', [\App\Http\Controllers\MaintenanceController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [\App\Http\Controllers\MaintenanceController::class, 'edit'])->name('edit');
    Route::put('/{id}', [\App\Http\Controllers\MaintenanceController::class, 'update'])->name('update');
    Route::delete('/{id}', [\App\Http\Controllers\MaintenanceController::class, 'destroy'])->name('destroy');
    Route::get("/{id}/download-dispatch", [\App\Http\Controllers\MaintenanceController::class, "downloadDispatchNote"])->name("maintenance.dispatch.download");
    Route::get('/statistics', [\App\Http\Controllers\MaintenanceController::class, 'statistics'])->name('statistics');
});



