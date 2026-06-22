<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Module;
use App\Models\Question;
use App\Models\User;
use App\Models\QuizAttempt;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class LearningSeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign key checks for speed if needed, but for 1000s it should be fine

        $user = User::firstOrCreate(
            ['email' => 'student@example.com'],
            [
                'name' => 'Student User',
                'password' => Hash::make('password'),
                'role' => 'student'
            ]
        );

        $course = Course::create([
            'title' => 'Mastering Web Development',
            'description' => 'A comprehensive course covering full-stack development.'
        ]);

        $moduleTitles = [
            'Frontend Fundamentals',
            'Backend Architecture',
            'Database Design',
            'DevOps & Deployment'
        ];

        foreach ($moduleTitles as $index => $title) {
            $module = Module::create([
                'course_id' => $course->id,
                'title' => $title,
                'content' => "Detailed learning content for {$title}. This covers core concepts, best practices, and practical examples.",
                'order' => $index + 1
            ]);

            // Create 300 questions per module to total 1200+
            $questions = [];
            for ($i = 1; $i <= 300; $i++) {
                $questions[] = [
                    'module_id' => $module->id,
                    'question_text' => "Question {$i} for module {$title}: What is a core principle discussed in this section?",
                    'options' => json_encode(['Option A', 'Option B', 'Option C', 'Option D']),
                    'correct_answer' => 'Option A',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Chunk inserts for performance
                if (count($questions) >= 100) {
                    Question::insert($questions);
                    $questions = [];
                }
            }
            if (!empty($questions)) {
                Question::insert($questions);
            }

            // Simulate some passed attempts for the first 3 modules
            if ($index < 3) {
                QuizAttempt::create([
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'module_id' => $module->id,
                    'score' => 85,
                    'total_questions' => 60,
                    'status' => 'passed',
                    'type' => 'module',
                    'attempt_number' => 1,
                    'created_at' => now()->subDays(10 - $index),
                    'updated_at' => now()->subDays(10 - $index)
                ]);
            } else {
                // Last module has some fails to demonstrate the learning curve and lockout
                QuizAttempt::create([
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'module_id' => $module->id,
                    'score' => 45,
                    'total_questions' => 60,
                    'status' => 'failed',
                    'type' => 'module',
                    'attempt_number' => 1,
                    'created_at' => now()->subDays(2),
                    'updated_at' => now()->subDays(2)
                ]);
            }
        }
    }
}
