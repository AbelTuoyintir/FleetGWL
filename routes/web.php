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

Route::middleware(['auth'])->prefix('ai-support')->name('ai-support.')->group(function () {
    Route::post('/chat', [AiSupportController::class, 'sendMessage'])->name('chat');
    Route::get('/history', [AiSupportController::class, 'getHistory'])->name('history');
});
