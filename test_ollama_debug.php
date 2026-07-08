<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Services\OllamaAiService;

$metrics = [
    'manager_score' => 85,
    'task_completion_rate' => 90,
    'deadline_adherence_rate' => 80,
    'productivity_score' => 85,
    'consistency_score' => 80,
];

// Call buildPrompt using reflection
$service = app(OllamaAiService::class);
$ref = new \ReflectionClass(OllamaAiService::class);
$method = $ref->getMethod('buildPrompt');
$method->setAccessible(true);
$prompt = $method->invoke($service, $metrics);

$url = 'http://localhost:11434/api/chat';
$payload = [
    'model' => 'llama3.2',
    'messages' => [
        [
            'role' => 'user',
            'content' => $prompt
        ]
    ],
    'stream' => false,
    'format' => 'json'
];

echo "Sending prompt with format json...\n";
$start = microtime(true);
try {
    $response = Http::timeout(60)->post($url, $payload);
    $end = microtime(true);
    echo "Time taken: " . ($end - $start) . " seconds\n";
    echo "Status: " . $response->status() . "\n";
    echo "Body: " . $response->body() . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
