<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Call;
use App\Models\User;
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
            ->get();

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
            ->get();

        return response()->json([
            'success' => true,
            'calls' => $calls,
        ]);
    }

    /**
     * Get contacts for click-to-call.
     * Admins can call drivers, Drivers can call admins/super_admins.
     */
    public function contacts()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        if ($user->isAdmin()) {
            // Get all drivers
            $contacts = User::where('role', 'driver')->get();
        } else {
            // Get all admins
            $contacts = User::whereIn('role', ['admin', 'super_admin'])->get();
        }

        return response()->json([
            'success' => true,
            'contacts' => $contacts,
        ]);
    }
}
