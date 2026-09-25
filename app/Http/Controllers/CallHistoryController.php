<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Call;
use App\Models\User;
use App\Services\CallService;
use Illuminate\Support\Facades\Auth;

class CallHistoryController extends Controller
{
    /**
     * Get call history for the authenticated user.
     */
    public function index()
    {
        $userId = Auth::id();

        $calls = Call::with(['caller', 'receiver'])
            ->where(function ($query) use ($userId) {
                $query->where('caller_id', $userId)
                      ->orWhere('receiver_id', $userId);
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(function ($call) {
                return CallService::isCallAllowed($call->caller, $call->receiver);
            })
            ->values();

        return response()->json([
            'success' => true,
            'calls' => $calls,
        ]);
    }

    /**
     * Get missed call history for the authenticated user.
     */
    public function missed()
    {
        $userId = Auth::id();

        $calls = Call::with(['caller', 'receiver'])
            ->where('receiver_id', $userId)
            ->where('status', 'missed')
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(function ($call) {
                return CallService::isCallAllowed($call->caller, $call->receiver);
            })
            ->values();

        return response()->json([
            'success' => true,
            'calls' => $calls,
        ]);
    }

    /**
     * Get contacts for click-to-call.
     * Admin -> Drivers
     * Driver -> Admins & Drivers
     * Other roles -> Empty list
     */
    public function contacts()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        if ($user->isAdmin()) {
            // Admin can call Drivers
            $contacts = User::where('role', 'driver')->get();
        } elseif ($user->isDriver()) {
            // Driver can call Admins and other Drivers
            $contacts = User::where(function ($query) use ($user) {
                $query->whereIn('role', ['admin', 'super_admin'])
                      ->orWhere(function ($q) use ($user) {
                          $q->where('role', 'driver')
                            ->where('id', '!=', $user->id);
                      });
            })->get();
        } else {
            // All other application roles are excluded from calling
            $contacts = collect([]);
        }

        return response()->json([
            'success' => true,
            'contacts' => $contacts,
        ]);
    }
}
