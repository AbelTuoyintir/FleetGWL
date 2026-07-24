<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CallService;
use App\Services\NotificationService;
use App\Models\Call;
use Illuminate\Support\Facades\Auth;

class CallController extends Controller
{
    protected $callService;
    protected $notificationService;

    public function __construct(CallService $callService, NotificationService $notificationService)
    {
        $this->callService = $callService;
        $this->notificationService = $notificationService;
    }

    /**
     * Start/Create a call.
     */
    public function start(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'call_type' => 'required|in:audio,video',
        ]);

        $callerId = Auth::id();
        $receiverId = $request->receiver_id;

        if ($callerId === (int) $receiverId) {
            return response()->json(['success' => false, 'message' => 'You cannot call yourself.'], 400);
        }

        $call = $this->callService->createCall($callerId, $receiverId, $request->call_type);

        return response()->json([
            'success' => true,
            'call' => $call,
        ]);
    }

    /**
     * Accept a call.
     */
    public function accept(Request $request)
    {
        $request->validate([
            'call_id' => 'required|exists:calls,id',
        ]);

        $call = $this->callService->acceptCall($request->call_id);

        return response()->json([
            'success' => true,
            'call' => $call,
        ]);
    }

    /**
     * Reject a call.
     */
    public function reject(Request $request)
    {
        $request->validate([
            'call_id' => 'required|exists:calls,id',
        ]);

        $call = $this->callService->rejectCall($request->call_id);

        return response()->json([
            'success' => true,
            'call' => $call,
        ]);
    }

    /**
     * Set call as busy.
     */
    public function busy(Request $request)
    {
        $request->validate([
            'call_id' => 'required|exists:calls,id',
        ]);

        $call = $this->callService->busyCall($request->call_id);

        return response()->json([
            'success' => true,
            'call' => $call,
        ]);
    }

    /**
     * End a call.
     */
    public function end(Request $request)
    {
        $request->validate([
            'call_id' => 'required|exists:calls,id',
        ]);

        $call = $this->callService->endCall($request->call_id);

        return response()->json([
            'success' => true,
            'call' => $call,
        ]);
    }

    /**
     * Mark a call as missed.
     */
    public function missed(Request $request)
    {
        $request->validate([
            'call_id' => 'required|exists:calls,id',
        ]);

        $call = $this->callService->missedCall($request->call_id);

        // Send a missed call notification to the receiver
        $this->notificationService->sendCallNotification(
            $call->receiver_id,
            'Missed Call',
            'You have a missed ' . $call->call_type . ' call from ' . ($call->caller->name ?? 'Unknown'),
            'warning'
        );

        return response()->json([
            'success' => true,
            'call' => $call,
        ]);
    }
}
