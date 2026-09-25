<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WebRTCService;
use App\Services\CallService;
use App\Models\Call;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class SignalController extends Controller
{
    protected $webRTCService;

    public function __construct(WebRTCService $webRTCService)
    {
        $this->webRTCService = $webRTCService;
    }

    /**
     * Validate signal authorization.
     */
    protected function authorizeSignal(Request $request): ?\Illuminate\Http\JsonResponse
    {
        $authUser = Auth::user();
        if (!$authUser) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $call = Call::find($request->call_id);
        if (!$call) {
            return response()->json(['success' => false, 'message' => 'Call not found.'], 404);
        }

        // Authenticated user must be a party to the call
        if ((int) $authUser->id !== (int) $call->caller_id && (int) $authUser->id !== (int) $call->receiver_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized call signal.'], 403);
        }

        // recipient_id must match the opposite party on the call
        $expectedRecipientId = ((int) $authUser->id === (int) $call->caller_id)
            ? (int) $call->receiver_id
            : (int) $call->caller_id;

        if ((int) $request->recipient_id !== $expectedRecipientId) {
            return response()->json(['success' => false, 'message' => 'Invalid signal recipient.'], 403);
        }

        // Check call relationship authorization
        $caller = User::find($call->caller_id);
        $receiver = User::find($call->receiver_id);

        if (!CallService::isCallAllowed($caller, $receiver)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized call relationship.'], 403);
        }

        return null;
    }

    /**
     * Send WebRTC Offer.
     */
    public function offer(Request $request)
    {
        $request->validate([
            'call_id' => 'required|exists:calls,id',
            'offer' => 'required',
            'recipient_id' => 'required|exists:users,id',
        ]);

        if ($authError = $this->authorizeSignal($request)) {
            return $authError;
        }

        $this->webRTCService->broadcastOffer($request->call_id, $request->offer, $request->recipient_id);

        return response()->json([
            'success' => true,
            'message' => 'Offer transmitted successfully.',
        ]);
    }

    /**
     * Send WebRTC Answer.
     */
    public function answer(Request $request)
    {
        $request->validate([
            'call_id' => 'required|exists:calls,id',
            'answer' => 'required',
            'recipient_id' => 'required|exists:users,id',
        ]);

        if ($authError = $this->authorizeSignal($request)) {
            return $authError;
        }

        $this->webRTCService->broadcastAnswer($request->call_id, $request->answer, $request->recipient_id);

        return response()->json([
            'success' => true,
            'message' => 'Answer transmitted successfully.',
        ]);
    }

    /**
     * Send WebRTC ICE Candidate.
     */
    public function iceCandidate(Request $request)
    {
        $request->validate([
            'call_id' => 'required|exists:calls,id',
            'candidate' => 'required',
            'recipient_id' => 'required|exists:users,id',
        ]);

        if ($authError = $this->authorizeSignal($request)) {
            return $authError;
        }

        $this->webRTCService->broadcastIceCandidate($request->call_id, $request->candidate, $request->recipient_id);

        return response()->json([
            'success' => true,
            'message' => 'ICE Candidate transmitted successfully.',
        ]);
    }
}
