<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class AiSupportService
{
    /**
     * Get an explanation for a concept.
     *
     * @param string $concept
     * @param string|null $context
     * @return string
     */
    public function explainConcept(string $concept, string $context = null): string
    {
        $cacheKey = 'ai_explanation_' . md5($concept . ($context ?? ''));

        return Cache::remember($cacheKey, 3600, function () use ($concept, $context) {
            // In a real scenario, this would call an AI API like OpenAI or Gemini.
            // For this project, we'll simulate a helpful AI response.

            return $this->generateMockExplanation($concept, $context);
        });
    }

    /**
     * Generate a mock explanation.
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
