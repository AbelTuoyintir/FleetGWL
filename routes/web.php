<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\AiSupportController;

// Existing routes
require __DIR__ . '/admin.php';
require __DIR__ . '/driver.php';
require __DIR__ . '/auth.php';

// New Learning Ecosystem Routes
Route::middleware(['auth'])->group(function () {
    // Dashboard & Learning Curve
    Route::get('/student/dashboard', [LearningController::class, 'dashboard'])->name('student.dashboard');

    // Course & Module Content
    Route::get('/learning/course/{course}/module/{module}', [LearningController::class, 'showModule'])->name('learning.module');

    // Quiz & Exam Logic
    Route::get('/quiz/course/{course}/module/{module}', [QuizController::class, 'startModuleQuiz'])->name('quiz.module');
    Route::get('/quiz/course/{course}/exam', [QuizController::class, 'startExam'])->name('quiz.exam');
    Route::post('/quiz/course/{course}/module/{module?}/submit', [QuizController::class, 'submit'])->name('quiz.submit');

    // AI Support
    Route::post('/ai-support/ask', [AiSupportController::class, 'ask'])->name('ai.ask');
});
