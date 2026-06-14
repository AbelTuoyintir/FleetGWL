<?php
// routes/web.php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\FuelMileageController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\DriverController;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

// Guest routes (only accessible when not logged in)
Route::middleware('guest')->group(function () {
    // Login routes
    Route::get('/login', [AuthenticationController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthenticationController::class, 'login'])->name('login.submit');
    
    // Password reset routes
    Route::get('/forgot-password', [AuthenticationController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [AuthenticationController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthenticationController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [AuthenticationController::class, 'resetPassword'])->name('password.update');
});

// Authenticated routes (require login)
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [AuthenticationController::class, 'logout'])->name('logout');
    Route::get('/logout', [AuthenticationController::class, 'logout']); // GET method for convenience
    
    // Dashboard (main landing after login)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Security & Device Management Routes
    Route::prefix('security')->name('security.')->group(function () {
        // Temporary handlers to keep links functional until a dedicated controller is added.
        Route::get('/activity', [ActivityLogController::class, 'index'])->name('activity');
        Route::get('/devices', fn () => redirect()->route('dashboard'))->name('devices');
        Route::delete('/devices/{device}', fn () => back())->name('devices.remove');
        Route::post('/devices/{device}/trust', fn () => back())->name('devices.trust');
        Route::post('/devices/{device}/untrust', fn () => back())->name('devices.untrust');
        Route::get('/settings', fn () => redirect()->route('dashboard'))->name('settings');
        Route::post('/settings/notifications', fn () => back())->name('settings.notifications');
        Route::get('/events/recent', fn () => response()->json(['success' => true, 'data' => []]))->name('events.recent');
        Route::post('/review/{eventId}', fn () => back())->name('review');
    });
    
    // Placeholder Two-Factor routes
    Route::prefix('2fa')->name('2fa.')->group(function () {
        Route::get('/setup', fn () => redirect()->route('dashboard'))->name('setup');
        Route::post('/enable', fn () => back())->name('enable');
        Route::post('/disable', fn () => back())->name('disable');
        Route::post('/verify', fn () => back())->name('verify');
        Route::get('/recovery-codes', fn () => redirect()->route('dashboard'))->name('recovery-codes');
        Route::post('/recovery-codes/regenerate', fn () => back())->name('recovery-codes.regenerate');
    });
});

/*
|--------------------------------------------------------------------------
| Role-Based Dashboard Routes (After Authentication)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Admin routes
    Route::middleware('role:admin,super_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/users', [UserController::class, 'index'])->name('users');
        Route::get('/security/logs', [ActivityLogController::class, 'index'])->name('security.logs');
    });
    
    // Driver routes
    Route::middleware('role:driver')->prefix('driver')->name('driver.')->group(function () {
        Route::get('/dashboard', [FuelMileageController::class, 'dashboard'])->name('dashboard');
        Route::get('/fuel-mileage', [FuelMileageController::class, 'dashboard'])->name('fuel-mileage.dashboard');
        Route::get('/profile', [DriverController::class, 'profile'])->name('profile');
        Route::post('/profile', [DriverController::class, 'updateProfile'])->name('profile.update');
    });
    
    // Technician routes
    Route::middleware('role:technician')->prefix('technician')->name('technician.')->group(function () {
        Route::get('/dashboard', [MaintenanceController::class, 'index'])->name('dashboard');
        Route::get('/maintenance', [MaintenanceController::class, 'index'])->name('maintenance');
    });
});

/*
|--------------------------------------------------------------------------
| Dashboard Refresh & Cache Routes (AJAX)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/dashboard/refresh', [DashboardController::class, 'getRefreshData'])->name('dashboard.refresh');
    Route::post('/dashboard/clear-cache', [DashboardController::class, 'clearCache'])->name('dashboard.clear-cache');
});


