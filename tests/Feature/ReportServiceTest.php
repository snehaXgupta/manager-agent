<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PerformanceReport;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_generate_and_save_performance_report(): void
    {
        $manager = User::create([
            'name' => 'Sarah',
            'email' => 'sarah@example.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
        ]);

        // Mock Ollama response
        config(['services.ollama.base_url' => 'http://localhost:11434']);
        config(['services.ollama.model' => 'llama3.1:8b']);
        $mockJson = json_encode([
            'summary' => 'Team performed well.',
            'strengths' => ['Good work.'],
            'weaknesses' => ['Slow start.'],
            'risks' => ['Burnout.'],
            'recommendations' => ['Take a break.'],
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

        $reportService = app(ReportService::class);
        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $report = $reportService->generateReport($manager->id, 'weekly', $startDate, $endDate);

        $this->assertDatabaseHas('performance_reports', [
            'id' => $report->id,
            'manager_id' => $manager->id,
            'report_type' => 'weekly',
        ]);

        $this->assertEquals('weekly', $report->report_type);
        $this->assertEquals($manager->id, $report->manager_id);
        $this->assertArrayHasKey('task_completion_rate', $report->metrics_json);
        $this->assertEquals('Healthy', $report->ai_insights_json['team_health']);
    }

    public function test_can_compare_reports(): void
    {
        $manager = User::create([
            'name' => 'Sarah',
            'email' => 'sarah@example.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
        ]);

        $reportService = app(ReportService::class);

        // Previous report
        $prevReport = PerformanceReport::create([
            'manager_id' => $manager->id,
            'report_type' => 'weekly',
            'period_start' => Carbon::now()->subDays(13),
            'period_end' => Carbon::now()->subDays(7),
            'metrics_json' => [
                'task_completion_rate' => 80.0,
                'deadline_adherence_rate' => 70.0,
                'productivity_score' => 75.0,
                'consistency_score' => 60.0,
                'manager_score' => 73.0,
            ],
            'ai_insights_json' => ['summary' => 'Previous report.'],
            'manager_score' => 73.0,
            'generated_at' => Carbon::now()->subDays(7),
        ]);

        // Current report
        $currReport = PerformanceReport::create([
            'manager_id' => $manager->id,
            'report_type' => 'weekly',
            'period_start' => Carbon::now()->subDays(6),
            'period_end' => Carbon::now(),
            'metrics_json' => [
                'task_completion_rate' => 90.0,
                'deadline_adherence_rate' => 80.0,
                'productivity_score' => 85.0,
                'consistency_score' => 70.0,
                'manager_score' => 83.0,
            ],
            'ai_insights_json' => ['summary' => 'Current report.'],
            'manager_score' => 83.0,
            'generated_at' => Carbon::now(),
        ]);

        $comparisonResult = $reportService->compareWithPrevious($currReport);

        $this->assertTrue($comparisonResult['has_previous']);
        $this->assertEquals($prevReport->id, $comparisonResult['previous_report_id']);
        $this->assertEquals(10.0, $comparisonResult['comparison']['manager_score']['diff']);
        $this->assertEquals(10.0, $comparisonResult['comparison']['task_completion_rate']['diff']);
        $this->assertEquals(10.0, $comparisonResult['comparison']['consistency_score']['diff']);
    }
}
