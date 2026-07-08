<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = app(App\Services\OllamaAiService::class);

echo "Sending request to Ollama service...\n";

try {
    $result = $service->generateInsights([
        'manager_score' => 85,
        'task_completion_rate' => 90,
        'deadline_adherence_rate' => 80,
        'productivity_score' => 85,
        'consistency_score' => 80,
    ]);

    echo "Response received:\n";
    print_r($result);
} catch (\Exception $e) {
    echo "Error calling Ollama: " . $e->getMessage() . "\n";
}
