<?php

namespace Tests\Feature;

use App\Services\OllamaAiService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OllamaAiServiceTest extends TestCase
{
    public function test_ollama_service_returns_fallback_insights_if_ollama_call_fails(): void
    {
        // Mock a failure response from Ollama
        Http::fake([
            '*' => Http::response([], 500)
        ]);

        $service = new OllamaAiService();
        $metrics = [
            'manager_score' => 85,
            'task_completion_rate' => 90,
            'deadline_adherence_rate' => 80,
            'productivity_score' => 85,
            'consistency_score' => 80,
        ];

        $insights = $service->generateInsights($metrics);

        $this->assertArrayHasKey('summary', $insights);
        $this->assertArrayHasKey('team_health', $insights);
        $this->assertEquals('Excellent', $insights['team_health']);
    }

    public function test_ollama_service_calls_local_api_and_returns_structured_insights(): void
    {
        // Configure local URL and model
        config(['services.ollama.base_url' => 'http://localhost:11434']);
        config(['services.ollama.model' => 'llama3.1:8b']);

        $mockJson = json_encode([
            'summary' => 'Ollama verified team summary.',
            'strengths' => ['Reliable completion rates'],
            'weaknesses' => ['Slight attendance drift'],
            'risks' => ['Burnout risks'],
            'recommendations' => ['Run regular meetings'],
            'team_health' => 'Healthy'
        ]);

        Http::fake([
            '*' => Http::response([
                'model' => 'llama3.1:8b',
                'message' => [
                    'role' => 'assistant',
                    'content' => $mockJson
                ],
                'done' => true
            ], 200)
        ]);

        $service = new OllamaAiService();
        $metrics = [
            'manager_score' => 75,
            'task_completion_rate' => 80,
            'deadline_adherence_rate' => 70,
            'productivity_score' => 75,
            'consistency_score' => 70,
        ];

        $insights = $service->generateInsights($metrics);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'localhost:11434') && 
                   str_contains($request->body(), 'manager_score');
        });

        $this->assertEquals('Ollama verified team summary.', $insights['summary']);
        $this->assertEquals('Healthy', $insights['team_health']);
        $this->assertEquals(['Reliable completion rates'], $insights['strengths']);
    }
}
