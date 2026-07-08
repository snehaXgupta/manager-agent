<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PerformanceReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeReportApiTest extends TestCase
{
    use RefreshDatabase;

    protected $manager;
    protected $employee;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::create([
            'name' => 'Sarah Manager',
            'email' => 'sarah@example.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
        ]);

        $this->employee = User::create([
            'name' => 'Rahul Employee',
            'email' => 'rahul@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'manager_id' => $this->manager->id,
        ]);

        $this->token = $this->createDeveloperTokenForUser($this->manager);
    }

    /**
     * Test API requires authentication.
     */
    public function test_employee_performance_api_requires_auth(): void
    {
        $response = $this->getJson("/api/employees/{$this->employee->id}/performance");
        $response->assertStatus(401);
    }

    /**
     * Test GET /api/employees/{id}/performance returns correct metrics.
     */
    public function test_employee_performance_api_returns_correct_metrics(): void
    {
        $response = $this->getJson("/api/employees/{$this->employee->id}/performance", [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'employee' => ['id', 'name', 'email', 'role'],
                'period',
                'date_range' => ['start', 'end'],
                'metrics' => [
                    'task_completion_rate',
                    'deadline_adherence_rate',
                    'productivity_score',
                    'consistency_score',
                    'developer_score',
                    'code_quality_score',
                    'reviews_score',
                    'delivery_speed_score',
                    'metrics_breakdown'
                ]
            ]);
    }

    /**
     * Test POST /api/employees/{id}/generate-report stores and returns report.
     */
    public function test_generate_employee_report_api_stores_and_returns_report(): void
    {
        $response = $this->postJson("/api/employees/{$this->employee->id}/generate-report", [
            'report_type' => 'weekly'
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'report' => [
                    'id',
                    'manager_id',
                    'report_type',
                    'metrics_json',
                    'ai_insights_json',
                    'manager_score'
                ],
                'comparison'
            ]);

        $this->assertDatabaseHas('performance_reports', [
            'manager_id' => $this->employee->id,
            'report_type' => 'weekly',
        ]);
    }

    /**
     * Test GET /api/employees/{id}/reports returns historical reports list.
     */
    public function test_employee_reports_list_api_returns_historical_reports(): void
    {
        // Generate a report first
        $report = PerformanceReport::create([
            'manager_id' => $this->employee->id,
            'report_type' => 'weekly',
            'period_start' => now()->subDays(7),
            'period_end' => now(),
            'metrics_json' => ['developer_score' => 90],
            'ai_insights_json' => ['summary' => 'Good'],
            'manager_score' => 90,
            'generated_at' => now(),
        ]);

        $response = $this->getJson("/api/employees/{$this->employee->id}/reports", [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'reports')
            ->assertJsonFragment([
                'id' => $report->id,
                'manager_id' => $this->employee->id
            ]);
    }

    /**
     * Test GET /api/employees/{id}/reports/{reportId} returns detail and comparisons.
     */
    public function test_employee_report_detail_api_returns_comparison_data(): void
    {
        // Create previous report
        $prevReport = PerformanceReport::create([
            'manager_id' => $this->employee->id,
            'report_type' => 'weekly',
            'period_start' => now()->subDays(14),
            'period_end' => now()->subDays(7),
            'metrics_json' => ['task_completion_rate' => 80],
            'manager_score' => 80,
            'generated_at' => now()->subDays(7),
        ]);

        // Create current report
        $currReport = PerformanceReport::create([
            'manager_id' => $this->employee->id,
            'report_type' => 'weekly',
            'period_start' => now()->subDays(7),
            'period_end' => now(),
            'metrics_json' => ['task_completion_rate' => 90],
            'manager_score' => 90,
            'generated_at' => now(),
        ]);

        $response = $this->getJson("/api/employees/{$this->employee->id}/reports/{$currReport->id}", [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'report' => ['id', 'manager_id', 'metrics_json'],
                'comparison' => [
                    'has_previous',
                    'previous_report_id',
                    'comparison' => [
                        'manager_score',
                        'task_completion_rate',
                    ]
                ]
            ])
            ->assertJsonFragment([
                'previous_report_id' => $prevReport->id
            ]);
    }
}
