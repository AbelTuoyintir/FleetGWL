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
use App\Http\Controllers\CallController;
use App\Http\Controllers\SignalController;
use App\Http\Controllers\CallHistoryController;

Route::prefix('ai-support')->name('ai-support.')->group(function () {
    Route::post('/chat', [AiSupportController::class, 'sendMessage'])->name('chat');
    Route::get('/history', [AiSupportController::class, 'getHistory'])->name('history');
});

Route::middleware(['auth'])->group(function () {
    // Calls
    Route::post('/calls/start', [CallController::class, 'start'])->name('calls.start');
    Route::post('/calls/accept', [CallController::class, 'accept'])->name('calls.accept');
    Route::post('/calls/reject', [CallController::class, 'reject'])->name('calls.reject');
    Route::post('/calls/busy', [CallController::class, 'busy'])->name('calls.busy');
    Route::post('/calls/end', [CallController::class, 'end'])->name('calls.end');
    Route::post('/calls/missed', [CallController::class, 'missed'])->name('calls.missed');

    // Signals
    Route::post('/signals/offer', [SignalController::class, 'offer'])->name('signals.offer');
    Route::post('/signals/answer', [SignalController::class, 'answer'])->name('signals.answer');
    Route::post('/signals/ice-candidate', [SignalController::class, 'iceCandidate'])->name('signals.ice-candidate');

    // History & Contacts
    Route::get('/calls/history', [CallHistoryController::class, 'index'])->name('calls.history');
    Route::get('/calls/missed-history', [CallHistoryController::class, 'missed'])->name('calls.missed-history');
    Route::get('/calls/contacts', [CallHistoryController::class, 'contacts'])->name('calls.contacts');
});
