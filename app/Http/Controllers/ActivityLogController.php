<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of the activity logs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request): View
    {
        $query = Activity::with('causer')->latest();

        // Filter by Log Name (Module)
        if ($request->has('module') && $request->module != '') {
            $query->where('log_name', $request->module);
        }

        // Filter by Event
        if ($request->has('event') && $request->event != '') {
            $query->where('event', $request->event);
        }

        // Filter by Causer (User)
        if ($request->has('user_id') && $request->user_id != '') {
            $query->where('causer_id', $request->user_id);
        }

        $activities = $query->paginate(15)->withQueryString();
        
        // Get unique log names and users for filters
        $modules = Activity::distinct()->pluck('log_name');
        $users = \App\Models\User::all();

        return view('admin.activity-logs', compact('activities', 'modules', 'users'));
    }

    /**
     * Display the specified activity log.
     *
     * @param  \Spatie\Activitylog\Models\Activity  $activity
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Activity $activity)
    {
        return response()->json([
            'attributes' => $activity->changes['attributes'] ?? [],
            'old' => $activity->changes['old'] ?? [],
        ]);
    }
}
