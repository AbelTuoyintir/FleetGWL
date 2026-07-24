<?php

namespace App\Services;

use App\Models\Call;
use App\Events\OfferCreated;
use App\Events\AnswerCreated;
use App\Events\IceCandidate;

class WebRTCService
{
    /**
     * Broadcast WebRTC Offer.
     */
    public function broadcastOffer($callId, $offer, $recipientId)
    {
        $call = Call::findOrFail($callId);
        broadcast(new OfferCreated($call, $offer, $recipientId));
        return true;
    }

    /**
     * Broadcast WebRTC Answer.
     */
    public function broadcastAnswer($callId, $answer, $recipientId)
    {
        $call = Call::findOrFail($callId);
        broadcast(new AnswerCreated($call, $answer, $recipientId));
        return true;
    }

    /**
     * Broadcast WebRTC ICE Candidate.
     */
    public function broadcastIceCandidate($callId, $candidate, $recipientId)
    {
        $call = Call::findOrFail($callId);
        broadcast(new IceCandidate($call, $candidate, $recipientId));
        return true;
    }
}
