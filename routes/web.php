<?php

use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

// Ensure the test client never gets redirected from `/`
// (Some auth scaffolding uses a home redirect route; this overrides it.)



// Routes removed to avoid duplication and name collision with admin.php
require __DIR__ . '/admin.php';
require __DIR__ . '/driver.php';
require __DIR__ . '/auth.php';

use App\Http\Controllers\AiSupportController;

Route::prefix('ai-support')->name('ai-support.')->group(function () {
    Route::post('/chat', [AiSupportController::class, 'sendMessage'])
        ->middleware('throttle:10,1')
        ->name('chat');
    Route::get('/history', [AiSupportController::class, 'getHistory'])
        ->middleware('throttle:20,1')
        ->name('history');
});
