<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WebRTCService;

class SignalController extends Controller
{
    protected $webRTCService;

    public function __construct(WebRTCService $webRTCService)
    {
        $this->webRTCService = $webRTCService;
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

        $this->webRTCService->broadcastIceCandidate($request->call_id, $request->candidate, $request->recipient_id);

        return response()->json([
            'success' => true,
            'message' => 'ICE Candidate transmitted successfully.',
        ]);
    }
}
