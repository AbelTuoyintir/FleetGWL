<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Module;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningSystemTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $course;
    protected $module;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'student']);
        $this->course = Course::create(['title' => 'Test Course']);
        $this->module = Module::create([
            'course_id' => $this->course->id,
            'title' => 'Test Module',
            'order' => 1
        ]);

        // Seed 100 questions
        for ($i = 1; $i <= 100; $i++) {
            Question::create([
                'module_id' => $this->module->id,
                'question_text' => "Q$i",
                'options' => ['A', 'B'],
                'correct_answer' => 'A'
            ]);
        }
    }

    public function test_module_quiz_limits_to_60_questions()
    {
        $this->actingAs($this->user);
        $response = $this->get(route('quiz.module', [$this->course, $this->module]));

        $response->assertStatus(200);
        $questions = $response->viewData('questions');
        $this->assertEquals(60, $questions->count());
    }

    public function test_exam_requires_all_modules_passed()
    {
        $this->actingAs($this->user);

        // Attempt exam before passing module
        $response = $this->get(route('quiz.exam', $this->course));
        $response->assertRedirect(route('student.dashboard'));
        $response->assertSessionHas('error', 'You must pass all module quizzes before you can take the final exam.');

        // Pass the module
        QuizAttempt::create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'module_id' => $this->module->id,
            'score' => 80,
            'total_questions' => 60,
            'status' => 'passed',
            'type' => 'module',
            'attempt_number' => 1
        ]);

        // Attempt exam after passing module
        $response = $this->get(route('quiz.exam', $this->course));
        $response->assertStatus(200);
        $questions = $response->viewData('questions');
        // Since we only have 100 questions total, it should take all of them up to 200
        $this->assertEquals(100, $questions->count());
    }

    public function test_fail_lockout_rule()
    {
        $this->actingAs($this->user);

        // Fail 4 times
        for ($i = 1; $i <= 4; $i++) {
            QuizAttempt::create([
                'user_id' => $this->user->id,
                'course_id' => $this->course->id,
                'module_id' => $this->module->id,
                'score' => 30,
                'total_questions' => 60,
                'status' => 'failed',
                'type' => 'module',
                'attempt_number' => $i
            ]);
        }

        $response = $this->get(route('quiz.module', [$this->course, $this->module]));
        $response->assertRedirect(route('learning.module', [$this->course, $this->module]));
        $response->assertSessionHas('error', 'You have failed this quiz 4 times. Please review the module material before trying again.');
    }

    public function test_ai_support_endpoint()
    {
        $this->actingAs($this->user);
        $response = $this->postJson(route('ai.ask'), [
            'concept' => 'Eloquent',
            'context' => 'Database'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'concept' => 'Eloquent'
            ]);

        $this->assertStringContainsString('built-in tool', $response->json('explanation'));
    }
}
