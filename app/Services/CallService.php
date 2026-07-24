<?php

namespace App\Services;

use App\Models\Call;
use App\Events\IncomingCall;
use App\Events\CallAccepted;
use App\Events\CallRejected;
use App\Events\CallEnded;
use App\Events\UserBusy;
use Carbon\Carbon;

class CallService
{
    /**
     * Create a new call signaling record and broadcast IncomingCall.
     */
    public function createCall($callerId, $receiverId, $callType)
    {
        // If the receiver is already in an active call, we can check that, but for now we just create the call
        $call = Call::create([
            'caller_id' => $callerId,
            'receiver_id' => $receiverId,
            'call_type' => $callType,
            'status' => 'calling',
        ]);

        // Load the caller relationship for name resolution in event
        $call->load('caller');

        // Broadcast IncomingCall to receiver
        broadcast(new IncomingCall($call));

        return $call;
    }

    /**
     * Accept an incoming call.
     */
    public function acceptCall($callId)
    {
        $call = Call::findOrFail($callId);
        $call->update([
            'status' => 'connected',
            'started_at' => Carbon::now(),
        ]);

        broadcast(new CallAccepted($call));

        return $call;
    }

    /**
     * Reject an incoming call.
     */
    public function rejectCall($callId)
    {
        $call = Call::findOrFail($callId);
        $call->update([
            'status' => 'rejected',
            'ended_at' => Carbon::now(),
        ]);

        broadcast(new CallRejected($call));

        return $call;
    }

    /**
     * Mark recipient as busy.
     */
    public function busyCall($callId)
    {
        $call = Call::findOrFail($callId);
        $call->update([
            'status' => 'ended',
            'ended_at' => Carbon::now(),
        ]);

        broadcast(new UserBusy($call));

        return $call;
    }

    /**
     * End an active call and calculate duration.
     */
    public function endCall($callId)
    {
        $call = Call::findOrFail($callId);

        $endedAt = Carbon::now();
        $duration = 0;

        if ($call->started_at) {
            $duration = abs($endedAt->diffInSeconds($call->started_at));
        }

        $call->update([
            'status' => 'ended',
            'ended_at' => $endedAt,
            'duration' => $duration,
        ]);

        // Broadcast CallEnded to both caller and receiver
        broadcast(new CallEnded($call, $call->caller_id));
        broadcast(new CallEnded($call, $call->receiver_id));

        return $call;
    }

    /**
     * Mark a call as missed.
     */
    public function missedCall($callId)
    {
        $call = Call::findOrFail($callId);
        $call->update([
            'status' => 'missed',
            'ended_at' => Carbon::now(),
        ]);

        broadcast(new CallEnded($call, $call->caller_id));
        broadcast(new CallEnded($call, $call->receiver_id));

        return $call;
    }
}
