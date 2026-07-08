<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\PerformanceReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProjectReportingUpgradeTest extends TestCase
{
    use RefreshDatabase;

    protected $manager;
    protected $employee1;
    protected $employee2;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure Ollama service goes to fallback mock mode without making HTTP requests
        Cache::put('ollama_offline', true, 600);

        $this->manager = User::create([
            'name' => 'Sarah Manager',
            'email' => 'sarah@example.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
        ]);

        $this->employee1 = User::create([
            'name' => 'Rahul Employee',
            'email' => 'rahul@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'manager_id' => $this->manager->id,
        ]);

        $this->employee2 = User::create([
            'name' => 'Jane Employee',
            'email' => 'jane@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'manager_id' => $this->manager->id,
        ]);
    }

    /**
     * Test manager can update a project's details including name, category, status, deadline, and members list.
     */
    public function test_manager_can_update_project_details(): void
    {
        $project = Project::create([
            'name' => 'Old Project Name',
            'description' => 'Old description.',
            'category' => 'Development',
            'status' => 'active',
            'manager_id' => $this->manager->id,
        ]);

        $project->members()->attach($this->employee1->id);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->patch(route('dashboard.projects.update', $project->id), [
                'name' => 'New Project Name',
                'description' => 'New description details.',
                'category' => 'Design',
                'status' => 'on_hold',
                'deadline' => '2026-12-31',
                'members' => [$this->employee2->id]
            ]);

        $response->assertRedirect();
        
        $project->refresh();

        $this->assertEquals('New Project Name', $project->name);
        $this->assertEquals('New description details.', $project->description);
        $this->assertEquals('Design', $project->category);
        $this->assertEquals('on_hold', $project->status);
        $this->assertEquals('2026-12-31', $project->deadline->format('Y-m-d'));
        
        // Assert old member is detached and new member is attached
        $this->assertFalse($project->members->contains($this->employee1->id));
        $this->assertTrue($project->members->contains($this->employee2->id));
    }

    /**
     * Test manager can archive and unarchive a project.
     */
    public function test_manager_can_archive_and_unarchive_project(): void
    {
        $project = Project::create([
            'name' => 'Workspace Project',
            'manager_id' => $this->manager->id,
            'is_archived' => false,
        ]);

        // Archive
        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->post(route('dashboard.projects.archive', $project->id));

        $response->assertRedirect(route('dashboard.projects.index'));
        $project->refresh();
        $this->assertTrue($project->is_archived);

        // Unarchive
        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->post(route('dashboard.projects.archive', $project->id));

        $response->assertRedirect(route('dashboard.projects.index'));
        $project->refresh();
        $this->assertFalse($project->is_archived);
    }

    /**
     * Test dynamic health score calculation components on the project details page.
     */
    public function test_project_health_score_calculation(): void
    {
        // Setup project with specific attributes to yield a target health score.
        // We will test the fallback to task-level deadline adherence when overall deadline is null.
        $project = Project::create([
            'name' => 'Health Test Project',
            'manager_id' => $this->manager->id,
            'deadline' => null, // triggers fallback to task-level deadline adherence
        ]);

        $project->members()->attach([$this->employee1->id, $this->employee2->id]);

        // Create 10 tasks in total:
        // - 5 Completed:
        //   - 4 Completed on time (deadline in future or null)
        //   - 1 Completed late (updated_at > deadline)
        // - 5 Pending:
        //   - 1 Pending past deadline (overdue)
        //   - 4 Pending on track
        
        // 4 Completed on time:
        for ($i = 0; $i < 4; $i++) {
            Task::create([
                'title' => "On Time Task $i",
                'project_id' => $project->id,
                'status' => 'completed',
                'deadline' => Carbon::now()->addDays(5),
                'updated_at' => Carbon::now(), // on time
                'assigned_to' => $this->employee1->id,
            ]);
        }

        // 1 Completed late:
        Task::create([
            'title' => "Late Task",
            'project_id' => $project->id,
            'status' => 'completed',
            'deadline' => Carbon::now()->subDays(5),
            'updated_at' => Carbon::now(), // late
            'assigned_to' => $this->employee1->id,
        ]);

        // 1 Overdue pending task:
        Task::create([
            'title' => "Overdue Pending",
            'project_id' => $project->id,
            'status' => 'pending',
            'deadline' => Carbon::now()->subDays(2),
            'assigned_to' => $this->employee1->id,
        ]);

        // 4 Pending on track tasks:
        for ($i = 0; $i < 4; $i++) {
            Task::create([
                'title' => "Pending On Track $i",
                'project_id' => $project->id,
                'status' => 'pending',
                'deadline' => Carbon::now()->addDays(5),
                'assigned_to' => $this->employee1->id,
            ]);
        }

        // Let's analyze metrics:
        // Total Tasks = 10
        // Completed = 5 (Completion Rate = 50%)
        // Progress component = 50% * 40 = 20 points
        
        // Deadline adherence fallback:
        // Completed on time = 4. Total completed = 5.
        // Adherence rate = 80%. Deadline component = 80% * 40 = 32 points.
        
        // Risk component:
        // Employee 1 has 10 tasks assigned. Active tasks (not completed) assigned to employee 1 is 5.
        // Penalty limit: overloaded is > 5 active/pending tasks. Employee 1 has exactly 5 pending tasks (not overloaded).
        // Employee 2 has 0 tasks assigned total. Penalty for inactive member = 3 points.
        // Repository is null, so git inactivity penalty = 0.
        // Risk component = 20 - 3 = 17 points.

        // Expected Total Health Score = round(20 + 32 + 17) = 69.

        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->get(route('dashboard.projects.show', $project->id));

        $response->assertStatus(200);

        $metrics = $response->viewData('projectMetrics');
        $this->assertEquals(69, $metrics['health_score']);
        $this->assertEquals(50, $metrics['task_completion_rate']);
        $this->assertEquals(80, $metrics['deadline_adherence_rate']);
        $this->assertContains('1 team member(s) have no tasks assigned.', $metrics['health_warnings']);
    }

    /**
     * Test manager can generate a specialized project completion report.
     */
    public function test_manager_can_generate_project_completion_report(): void
    {
        $project = Project::create([
            'name' => 'Alpha Platform',
            'manager_id' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->post(route('dashboard.reports.store'), [
                'report_type' => 'project_completion',
                'project_id' => $project->id,
            ]);

        $report = PerformanceReport::where('manager_id', $this->manager->id)
            ->where('report_type', 'project_completion')
            ->first();

        $this->assertNotNull($report);
        $response->assertRedirect(route('dashboard.reports.show', $report->id));

        $this->assertEquals('project_completion', $report->report_type);
        $this->assertEquals($project->id, $report->metrics_json['project_id']);
        $this->assertEquals('Alpha Platform', $report->metrics_json['project_name']);
    }

    /**
     * Test manager can generate a specialized delayed projects report.
     */
    public function test_manager_can_generate_delayed_projects_report(): void
    {
        // Create an active project that is past its deadline
        Project::create([
            'name' => 'Overdue Workspace',
            'manager_id' => $this->manager->id,
            'deadline' => Carbon::now()->subDays(5),
            'status' => 'active',
            'is_archived' => false,
        ]);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->post(route('dashboard.reports.store'), [
                'report_type' => 'delayed_projects',
            ]);

        $report = PerformanceReport::where('manager_id', $this->manager->id)
            ->where('report_type', 'delayed_projects')
            ->first();

        $this->assertNotNull($report);
        $response->assertRedirect(route('dashboard.reports.show', $report->id));

        $this->assertEquals('delayed_projects', $report->report_type);
        $this->assertGreaterThanOrEqual(1, $report->metrics_json['total_delayed_count']);
        $this->assertEquals('Overdue Workspace', $report->metrics_json['delayed_projects'][0]['name']);
    }

    /**
     * Test manager can generate a specialized team-wise project report.
     */
    public function test_manager_can_generate_team_wise_projects_report(): void
    {
        // Create a team
        $team = Team::create([
            'name' => 'Backend Squad',
            'manager_id' => $this->manager->id,
        ]);

        $project = Project::create([
            'name' => 'Services Project',
            'manager_id' => $this->manager->id,
        ]);

        // Add task with team_id and project_id to map them
        Task::create([
            'title' => 'Squad Task',
            'project_id' => $project->id,
            'team_id' => $team->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->post(route('dashboard.reports.store'), [
                'report_type' => 'team_wise_projects',
            ]);

        $report = PerformanceReport::where('manager_id', $this->manager->id)
            ->where('report_type', 'team_wise_projects')
            ->first();

        $this->assertNotNull($report);
        $response->assertRedirect(route('dashboard.reports.show', $report->id));

        $this->assertEquals('team_wise_projects', $report->report_type);
        $this->assertGreaterThanOrEqual(1, $report->metrics_json['total_teams_count']);
        $this->assertEquals('Backend Squad', $report->metrics_json['teams_data'][0]['team_name']);
        $this->assertContains('Services Project', $report->metrics_json['teams_data'][0]['projects']);
    }
}
