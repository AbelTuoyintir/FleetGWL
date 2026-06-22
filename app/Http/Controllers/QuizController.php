<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Module;
use App\Models\Question;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class QuizController extends Controller
{
    /**
     * Start a module quiz.
     */
    public function startModuleQuiz(Course $course, Module $module)
    {
        // Enforce 4-fail lockout rule
        $failCount = QuizAttempt::where('user_id', Auth::id())
            ->where('module_id', $module->id)
            ->where('status', 'failed')
            ->count();

        if ($failCount >= 4) {
            return redirect()->route('learning.module', [$course, $module])
                ->with('error', 'You have failed this quiz 4 times. Please review the module material before trying again.');
        }

        // Get 60 random questions
        $questions = Question::where('module_id', $module->id)
            ->inRandomOrder()
            ->limit(60)
            ->get();

        if ($questions->count() === 0) {
            return back()->with('error', 'No questions available for this module yet.');
        }

        return view('quiz.show', [
            'type' => 'module',
            'course' => $course,
            'module' => $module,
            'questions' => $questions
        ]);
    }

    /**
     * Start a course exam.
     */
    public function startExam(Course $course)
    {
        $user = Auth::user();

        // Enforce exam prerequisite: all modules passed
        $moduleIds = $course->modules()->pluck('id');
        $passedModuleIds = QuizAttempt::where('user_id', $user->id)
            ->whereIn('module_id', $moduleIds)
            ->where('status', 'passed')
            ->distinct()
            ->pluck('module_id');

        if ($passedModuleIds->count() < $moduleIds->count()) {
            return redirect()->route('student.dashboard')
                ->with('error', 'You must pass all module quizzes before you can take the final exam.');
        }

        // Get 200 random questions from across all modules in the course
        $questions = Question::whereIn('module_id', $moduleIds)
            ->inRandomOrder()
            ->limit(200)
            ->get();

        if ($questions->count() < 20) { // arbitrary minimum for a "200 question" bank
             return back()->with('error', 'Not enough questions in the bank for a full exam.');
        }

        return view('quiz.show', [
            'type' => 'exam',
            'course' => $course,
            'module' => null,
            'questions' => $questions
        ]);
    }

    /**
     * Submit quiz/exam results.
     */
    public function submit(Request $request, Course $course, Module $module = null)
    {
        $user = Auth::user();
        $answers = $request->input('answers', []);
        $questionIds = array_keys($answers);

        $questions = Question::whereIn('id', $questionIds)->get();
        $correctCount = 0;

        foreach ($questions as $question) {
            if ($answers[$question->id] == $question->correct_answer) {
                $correctCount++;
            }
        }

        $totalQuestions = $questions->count();
        $score = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100) : 0;
        $status = $score >= 70 ? 'passed' : 'failed';
        $type = $module ? 'module' : 'exam';

        $attemptNumber = QuizAttempt::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('module_id', $module ? $module->id : null)
            ->count() + 1;

        QuizAttempt::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'module_id' => $module ? $module->id : null,
            'score' => $score,
            'total_questions' => $totalQuestions,
            'status' => $status,
            'type' => $type,
            'attempt_number' => $attemptNumber
        ]);

        return redirect()->route('student.dashboard')
            ->with('success', "You finished the {$type} with a score of {$score}%!");
    }
}
