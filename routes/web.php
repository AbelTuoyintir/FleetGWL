<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Events\IncomingCall;
use App\Models\User;
use App\Models\Call;

// Route::get('/', function () {
//     return view('welcome');
// });

// Ensure the test client never gets redirected from `/`
// (Some auth scaffolding uses a home redirect route; this overrides it.)

/*
|--------------------------------------------------------------------------
| Debug Route: Test Broadcasting
|--------------------------------------------------------------------------
|
| Visit /test-broadcast after logging in as any user to manually trigger
| an IncomingCall event to a specific receiver.
|
| Usage: GET /test-broadcast?receiver_id=5
|
| If you don't see the event on the receiver's console, the broadcast
| pipeline is broken (check .env BROADCAST_CONNECTION, Reverb server, etc.)
|
*/
Route::get('/test-broadcast', function () {
    $receiverId = request('receiver_id', 1);

    // Find the receiver user
    $receiver = User::find($receiverId);
    if (!$receiver) {
        return 'Error: Receiver user not found with ID: ' . $receiverId;
    }

    // Find the caller (use the currently logged-in user, or first admin)
    $caller = auth()->user() ?? User::where('role', 'admin')->first();
    if (!$caller) {
        return 'Error: No caller user available. Log in first or seed an admin user.';
    }

    // Create a test call record
    $call = Call::create([
        'caller_id' => $caller->id,
        'receiver_id' => $receiverId,
        'call_type' => 'audio',
        'status' => 'calling',
    ]);

    // Reload with caller relationship
    $call->load('caller');

    Log::info('[Test Broadcast] Broadcasting IncomingCall', [
        'call_id' => $call->id,
        'caller_id' => $caller->id,
        'receiver_id' => $receiverId,
        'channel' => 'user.' . $receiverId,
    ]);

    try {
        broadcast(new IncomingCall($call));
    } catch (\Exception $e) {
        Log::error('[Test Broadcast] Broadcast failed: ' . $e->getMessage());
        return 'Broadcast FAILED: ' . $e->getMessage();
    }

    return 'Broadcast sent to user.' . $receiverId . ' (call_id: ' . $call->id . '). Check receiver console for Echo events.';
})->middleware('auth');



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

    // Driver GPS Location Tracking
    Route::post('/driver/location', [\App\Http\Controllers\DriverTrackingController::class, 'updateLocation'])->name('driver.location.update');
});
