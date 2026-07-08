<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Team;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\PerformanceReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $manager;
    protected $employee;

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
    }

    /**
     * Test manager can access teams page.
     */
    public function test_manager_can_access_teams_page(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->get(route('dashboard.teams.index'));

        $response->assertStatus(200);
        $response->assertSee('Operational Teams');
    }

    /**
     * Test manager can create a team with members.
     */
    public function test_manager_can_create_team(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->post(route('dashboard.teams.store'), [
                'name' => 'Frontend Squad',
                'members' => [$this->employee->id]
            ]);

        $response->assertRedirect(route('dashboard.teams.index'));
        $this->assertDatabaseHas('teams', ['name' => 'Frontend Squad']);
        
        $team = Team::where('name', 'Frontend Squad')->first();
        $this->assertTrue($team->members->contains($this->employee->id));
    }

    /**
     * Test manager can view team show page and calculate metrics.
     */
    public function test_manager_can_view_team_dashboard(): void
    {
        $team = Team::create([
            'name' => 'Backend Squad',
            'manager_id' => $this->manager->id
        ]);
        $team->members()->attach($this->employee->id);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->get(route('dashboard.teams.show', $team->id));

        $response->assertStatus(200);
        $response->assertSee('Backend Squad');
        $response->assertSee('AI Team Analysis');
    }

    /**
     * Test manager can schedule meeting for a team.
     */
    public function test_manager_can_schedule_meeting(): void
    {
        $team = Team::create([
            'name' => 'QA Squad',
            'manager_id' => $this->manager->id
        ]);

        $response = $this->from(route('dashboard.teams.show', $team->id))
            ->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->post(route('dashboard.teams.meetings.store', $team->id), [
                'title' => 'Weekly Sync',
                'description' => 'Discuss release items.',
                'meeting_date' => now()->addDay()->format('Y-m-d'),
                'meeting_time' => '10:00:00',
                'duration' => 30,
            ]);

        $response->assertRedirect(route('dashboard.teams.show', $team->id));
        $this->assertDatabaseHas('meetings', [
            'title' => 'Weekly Sync',
            'team_id' => $team->id,
            'manager_id' => $this->manager->id
        ]);
    }

    /**
     * Test manager can save meeting notes.
     */
    public function test_manager_can_post_meeting_notes(): void
    {
        $team = Team::create([
            'name' => 'Design Squad',
            'manager_id' => $this->manager->id
        ]);

        $meeting = Meeting::create([
            'title' => 'Design Review',
            'scheduled_at' => now()->subHour(),
            'team_id' => $team->id,
            'manager_id' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->post(route('dashboard.teams.meetings.notes.update', [$team->id, $meeting->id]), [
                'meeting_notes' => 'Decided on HSL color scheme and dark mode design details.',
            ]);

        $response->assertRedirect(route('dashboard.teams.show', $team->id));
        $this->assertDatabaseHas('meetings', [
            'id' => $meeting->id,
            'meeting_notes' => 'Decided on HSL color scheme and dark mode design details.'
        ]);
    }

    /**
     * Test manager can assign task to a team.
     */
    public function test_manager_can_assign_task_to_team(): void
    {
        $team = Team::create([
            'name' => 'Data Squad',
            'manager_id' => $this->manager->id
        ]);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->post(route('dashboard.teams.tasks.store', $team->id), [
                'title' => 'Build Report Engine',
                'description' => 'Create CSV/PDF outputs.',
                'deadline' => now()->addDays(5)->format('Y-m-d'),
                'assigned_to' => $this->employee->id
            ]);

        $response->assertRedirect(route('dashboard.teams.show', $team->id));
        $this->assertDatabaseHas('tasks', [
            'title' => 'Build Report Engine',
            'team_id' => $team->id,
            'assigned_to' => $this->employee->id
        ]);
    }

    /**
     * Test manager can view a dedicated performance report show page.
     */
    public function test_manager_can_view_performance_report_detail_page(): void
    {
        $report = PerformanceReport::create([
            'manager_id' => $this->manager->id,
            'report_type' => 'weekly',
            'period_start' => now()->subDays(7),
            'period_end' => now(),
            'manager_score' => 85.0,
            'metrics_json' => [
                'team_size' => 1,
                'task_completion_rate' => 90.0,
                'deadline_adherence_rate' => 80.0,
                'productivity_score' => 85.0,
                'consistency_score' => 80.0,
                'metrics_breakdown' => [
                    'total_assigned_tasks' => 10,
                    'completed_tasks' => 9,
                    'completed_on_time_tasks' => 8,
                    'total_hours_logged' => 40,
                    'expected_hours' => 40,
                ],
                'predictive' => [
                    'team_health' => [
                        'team_health_score' => 88.0,
                        'status' => 'Healthy',
                        'metrics' => [
                            'attendance_health' => 90.0,
                            'productivity_health' => 85.0,
                            'delivery_health' => 80.0,
                        ]
                    ]
                ]
            ],
            'ai_insights_json' => [
                'summary' => 'Great performance this week.',
                'strengths' => ['On-time delivery.'],
                'weaknesses' => ['Minor log entry gaps.'],
                'risks' => ['Burnout risk.'],
                'recommendations' => ['Encourage break times.'],
                'team_health' => 'Healthy'
            ],
            'generated_at' => now(),
        ]);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->get(route('dashboard.reports.show', $report->id));

        $response->assertStatus(200);
        $response->assertSee('Performance Report Details');
        $response->assertSee('Great performance this week.');
        $response->assertSee('On-time delivery.');
    }

    /**
     * Test manager can generate performance report on demand.
     */
    public function test_manager_can_generate_performance_report_on_demand(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->post(route('dashboard.reports.store'), [
                'report_type' => 'weekly',
                'start_date' => now()->subDays(7)->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
            ]);

        // Expect a redirect to the show route
        $report = PerformanceReport::where('manager_id', $this->manager->id)->first();
        $this->assertNotNull($report);
        $response->assertRedirect(route('dashboard.reports.show', $report->id));

        $this->assertDatabaseHas('performance_reports', [
            'manager_id' => $this->manager->id,
            'report_type' => 'weekly',
        ]);
    }

    /**
     * Test manager can filter reports by type and date range.
     */
    public function test_manager_can_filter_reports_by_type_and_dates(): void
    {
        // 1. Create a daily report
        $dailyReport = PerformanceReport::create([
            'manager_id' => $this->manager->id,
            'report_type' => 'daily',
            'period_start' => now()->subDay(),
            'period_end' => now(),
            'manager_score' => 75.0,
            'metrics_json' => ['task_completion_rate' => 70],
            'ai_insights_json' => ['summary' => 'Daily summary text here'],
            'generated_at' => now()->subDay(),
        ]);

        // 2. Create a weekly report
        $weeklyReport = PerformanceReport::create([
            'manager_id' => $this->manager->id,
            'report_type' => 'weekly',
            'period_start' => now()->subDays(7),
            'period_end' => now(),
            'manager_score' => 85.0,
            'metrics_json' => ['task_completion_rate' => 80],
            'ai_insights_json' => ['summary' => 'Weekly summary text here'],
            'generated_at' => now(),
        ]);

        $dailyId = '#REP-' . str_pad($dailyReport->id, 5, '0', STR_PAD_LEFT);
        $weeklyId = '#REP-' . str_pad($weeklyReport->id, 5, '0', STR_PAD_LEFT);

        // Filter by type = weekly
        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->get(route('dashboard.reports.index', ['type' => 'weekly']));

        $response->assertStatus(200);
        $response->assertSee($weeklyId);
        $response->assertDontSee($dailyId);

        // Filter by date range that only covers the daily report
        $response2 = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->get(route('dashboard.reports.index', [
                'start_date' => now()->subDays(2)->format('Y-m-d'),
                'end_date' => now()->subDay()->endOfDay()->format('Y-m-d'),
            ]));

        $response2->assertStatus(200);
        $response2->assertSee($dailyId);
        $response2->assertDontSee($weeklyId);
    }

    /**
     * Test manager performance report detail shows comparison trends with previous report.
     */
    public function test_performance_report_details_shows_comparison_data(): void
    {
        // Previous report
        PerformanceReport::create([
            'manager_id' => $this->manager->id,
            'report_type' => 'weekly',
            'period_start' => now()->subDays(14),
            'period_end' => now()->subDays(7),
            'manager_score' => 80.0,
            'metrics_json' => [
                'task_completion_rate' => 80.0,
                'deadline_adherence_rate' => 80.0,
                'productivity_score' => 80.0,
                'consistency_score' => 80.0,
            ],
            'ai_insights_json' => ['summary' => 'Previous report.'],
            'generated_at' => now()->subDays(7),
        ]);

        // Current report (higher scores, indicating improvement/decline)
        $currReport = PerformanceReport::create([
            'manager_id' => $this->manager->id,
            'report_type' => 'weekly',
            'period_start' => now()->subDays(7),
            'period_end' => now(),
            'manager_score' => 90.0,
            'metrics_json' => [
                'task_completion_rate' => 90.0,
                'deadline_adherence_rate' => 85.0,
                'productivity_score' => 95.0,
                'consistency_score' => 75.0, // decline
            ],
            'ai_insights_json' => ['summary' => 'Current report.'],
            'generated_at' => now(),
        ]);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->get(route('dashboard.reports.show', $currReport->id));

        $response->assertStatus(200);
        $response->assertSee('Performance Trend Comparison');
        // Let's assert we see the current and previous values
        $response->assertSee('90%');
        $response->assertSee('prev: 80%');
        // Let's assert we see the diff values (like +10% or -5%)
        $response->assertSee('+10%');
        $response->assertSee('-5%');
    }

    /**
     * Test teams list pagination.
     */
    public function test_teams_page_is_paginated(): void
    {
        // Create 25 teams managed by this manager
        for ($i = 1; $i <= 25; $i++) {
            Team::create([
                'name' => "Team $i",
                'manager_id' => $this->manager->id,
            ]);
        }

        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->get(route('dashboard.teams.index'));

        $response->assertStatus(200);
        
        $teams = $response->viewData('teams');
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $teams);
        $this->assertEquals(25, $teams->total());
        $this->assertEquals(20, $teams->perPage());
        $this->assertCount(20, $teams->items());
    }

    /**
     * Test teams page shows team lead name if team members have a team lead manager, otherwise manager fallback.
     */
    public function test_teams_page_shows_team_lead_name_or_manager_fallback(): void
    {
        // Create a team lead user who reports to Sarah Manager
        $teamLead = User::create([
            'name' => 'John TeamLead',
            'email' => 'john.lead@example.com',
            'password' => bcrypt('password'),
            'role' => 'team_lead',
            'manager_id' => $this->manager->id,
        ]);

        // Create an employee user who reports to the team lead
        $subEmployee = User::create([
            'name' => 'Sub Employee',
            'email' => 'sub.emp@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'manager_id' => $teamLead->id,
        ]);

        // Create a team with the sub employee (their manager is a team lead)
        $teamWithLead = Team::create([
            'name' => 'Lead-led Team',
            'manager_id' => $this->manager->id,
        ]);
        $teamWithLead->members()->attach($subEmployee->id);

        // Create another team with no members (or members who don't have a team lead)
        $teamNoLead = Team::create([
            'name' => 'Manager-led Team',
            'manager_id' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->get(route('dashboard.teams.index'));

        $response->assertStatus(200);
        $response->assertSee('Lead: John TeamLead');
        $response->assertSee('Manager: Sarah Manager');
    }
}

