<?php

namespace App\Services;

use App\Models\Call;
use App\Events\OfferCreated;
use App\Events\AnswerCreated;
use App\Events\IceCandidate;

class WebRTCService
{
    /**
     * Helper to safely execute a real-time broadcast and handle connection errors gracefully.
     */
    protected function safeBroadcast($event)
    {
        try {
            broadcast($event);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[Broadcasting] Failed to dispatch real-time broadcast. Ensure Reverb/WebSocket server is running.', [
                'event' => get_class($event),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Broadcast WebRTC Offer.
     */
    public function broadcastOffer($callId, $offer, $recipientId)
    {
        $call = Call::findOrFail($callId);
        $this->safeBroadcast(new OfferCreated($call, $offer, $recipientId));
        return true;
    }

    /**
     * Broadcast WebRTC Answer.
     */
    public function broadcastAnswer($callId, $answer, $recipientId)
    {
        $call = Call::findOrFail($callId);
        $this->safeBroadcast(new AnswerCreated($call, $answer, $recipientId));
        return true;
    }

    /**
     * Broadcast WebRTC ICE Candidate.
     */
    public function broadcastIceCandidate($callId, $candidate, $recipientId)
    {
        $call = Call::findOrFail($callId);
        $this->safeBroadcast(new IceCandidate($call, $candidate, $recipientId));
        return true;
    }
}
