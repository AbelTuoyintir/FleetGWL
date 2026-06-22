<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Module;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LearningController extends Controller
{
    /**
     * Display the student dashboard.
     */
    public function dashboard()
    {
        $user = Auth::user();
        $courses = Course::with(['modules'])->get();

        // Get data for the pictorial learning curve (chronological)
        $attempts = QuizAttempt::where('user_id', $user->id)
            ->with(['module'])
            ->orderBy('created_at', 'asc')
            ->get();

        $learningCurve = $attempts->map(function ($attempt) {
            return [
                'date' => $attempt->created_at->format('M d'),
                'score' => $attempt->score,
                'status' => $attempt->status,
                'type' => $attempt->type,
                'label' => $attempt->type === 'module' ? ($attempt->module->title ?? 'Module') : 'Exam'
            ];
        });

        // Separate collection for recent attempts (newest first)
        $recentAttempts = QuizAttempt::where('user_id', $user->id)
            ->with(['module'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('student.dashboard', compact('courses', 'learningCurve', 'recentAttempts'));
    }

    /**
     * Display a specific module.
     */
    public function showModule(Course $course, Module $module)
    {
        // Get fail count for lockout rule
        $failCount = QuizAttempt::where('user_id', Auth::id())
            ->where('module_id', $module->id)
            ->where('status', 'failed')
            ->count();

        $lockedOut = $failCount >= 4;

        return view('learning.module', compact('course', 'module', 'lockedOut', 'failCount'));
    }
}
