<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CallService;
use App\Services\NotificationService;
use App\Models\Call;
use App\Models\User;
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
     * Validate call authorization for existing call.
     */
    protected function authorizeCallAccess(Call $call, bool $mustBeReceiver = false): ?\Illuminate\Http\JsonResponse
    {
        $authUser = Auth::user();
        if (!$authUser) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        if ((int) $authUser->id !== (int) $call->caller_id && (int) $authUser->id !== (int) $call->receiver_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized call access.'], 403);
        }

        if ($mustBeReceiver && (int) $authUser->id !== (int) $call->receiver_id) {
            return response()->json(['success' => false, 'message' => 'Only the call receiver can perform this action.'], 403);
        }

        $caller = User::find($call->caller_id);
        $receiver = User::find($call->receiver_id);

        if (!CallService::isCallAllowed($caller, $receiver)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized call relationship.'], 403);
        }

        return null;
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

        $caller = Auth::user();
        if (!$caller) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $receiver = User::find($request->receiver_id);
        if (!$receiver) {
            return response()->json(['success' => false, 'message' => 'Receiver not found.'], 404);
        }

        if (!CallService::isCallAllowed($caller, $receiver)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized call relationship.',
            ], 403);
        }

        $call = $this->callService->createCall($caller->id, $receiver->id, $request->call_type);

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

        $call = Call::findOrFail($request->call_id);
        if ($authError = $this->authorizeCallAccess($call, true)) {
            return $authError;
        }

        $call = $this->callService->acceptCall($call->id);

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

        $call = Call::findOrFail($request->call_id);
        if ($authError = $this->authorizeCallAccess($call)) {
            return $authError;
        }

        $call = $this->callService->rejectCall($call->id);

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

        $call = Call::findOrFail($request->call_id);
        if ($authError = $this->authorizeCallAccess($call)) {
            return $authError;
        }

        $call = $this->callService->busyCall($call->id);

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

        $call = Call::findOrFail($request->call_id);
        if ($authError = $this->authorizeCallAccess($call)) {
            return $authError;
        }

        $call = $this->callService->endCall($call->id);

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

        $call = Call::findOrFail($request->call_id);
        if ($authError = $this->authorizeCallAccess($call)) {
            return $authError;
        }

        $call = $this->callService->missedCall($call->id);

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
