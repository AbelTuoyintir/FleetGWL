<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiSupportService
{
    /**
     * Get an explanation for a concept using a real LLM if configured.
     */
    public function explainConcept(string $concept, string $context = null): string
    {
        $apiKey = config('services.ai.key');
        $baseUrl = config('services.ai.base_url', 'https://api.openai.com/v1');
        $model = config('services.ai.model', 'gpt-3.5-turbo');

        if (!$apiKey) {
            return $this->generateMockExplanation($concept, $context);
        }

        $cacheKey = 'ai_llm_explanation_' . md5($concept . ($context ?? ''));

        return Cache::remember($cacheKey, 3600, function () use ($concept, $context, $apiKey, $baseUrl, $model) {
            try {
                $response = Http::withToken($apiKey)
                    ->timeout(10)
                    ->post("{$baseUrl}/chat/completions", [
                        'model' => $model,
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => "You are an empathetic, human-like teaching assistant for the GWCL Asset Portal learning platform.
                                The platform is a vehicle maintenance and fuel management system with an integrated learning section.
                                Your goal is to explain complex technical concepts to students simply and warmly.

                                System Rules You Should Know:
                                - Module Quizzes: 60 random questions.
                                - Final Exams: 200 random questions (requires passing all modules first).
                                - Lockout Rule: 4 failed attempts on a module quiz denies access until further study.
                                - Tracking: We have a live vehicle tracking dashboard.

                                If asked about these, respond like a helpful staff member. Always be encouraging and relatable."
                            ],
                            [
                                'role' => 'user',
                                'content' => "Explain this concept to me: '{$concept}'" . ($context ? " in the context of {$context}." : ".")
                            ]
                        ],
                        'temperature' => 0.7,
                    ]);

                if ($response->successful()) {
                    return $response->json('choices.0.message.content');
                }

                Log::error('AI API Error: ' . $response->body());
            } catch (\Exception $e) {
                Log::error('AI Service Exception: ' . $e->getMessage());
            }

            return $this->generateMockExplanation($concept, $context);
        });
    }

    /**
     * Generate a mock explanation (fallback).
     */
    protected function generateMockExplanation(string $concept, string $context = null): string
    {
        $responses = [
            'Eloquent' => 'Eloquent ORM is Laravel\'s built-in tool for interacting with your database. It uses a "Model" to represent each table, making it easy to create, read, update, and delete records using PHP syntax instead of raw SQL.',
            'Middleware' => 'Middleware provides a convenient mechanism for inspecting and filtering HTTP requests entering your application. For example, Laravel includes a middleware that verifies the user of your application is authenticated.',
            'Migrations' => 'Migrations are like version control for your database, allowing your team to modify and share the application\'s database schema. They are typically paired with Laravel\'s schema builder to build your application\'s database schema.',
            'Vite' => 'Vite is a modern frontend build tool that provides an extremely fast development environment and bundles your code for production. In Laravel, it handles compiling your CSS and JavaScript assets.',
            'Blade' => 'Blade is the powerful templating engine included with Laravel. Unlike some PHP templating engines, Blade does not restrict you from using plain PHP code in your templates, and it compiles into plain PHP code for performance.',
        ];

        foreach ($responses as $key => $explanation) {
            if (stripos($concept, $key) !== false) {
                return $explanation;
            }
        }

        return "AI Explanation for '{$concept}': This is a core concept in modern web development. " .
               ($context ? "In the context of {$context}, it " : "It ") .
               "refers to a system or pattern designed to improve modularity, scalability, and developer efficiency. To learn more, try asking about specific components or check the official documentation.";
    }
}
