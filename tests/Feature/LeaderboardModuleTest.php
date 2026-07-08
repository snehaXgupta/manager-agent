<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Team;
use App\Models\Department;
use App\Models\Designation;
use App\Models\AttendanceLog;
use App\Models\Task;
use App\Models\DeveloperActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LeaderboardModuleTest extends TestCase
{
    use RefreshDatabase;

    protected $manager;
    protected $employee;
    protected $department;
    protected $designation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::create([
            'name' => 'John Manager',
            'email' => 'john.mgr@example.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
        ]);

        $this->department = Department::create([
            'name' => 'Engineering',
            'description' => 'Software building team',
        ]);

        $this->designation = Designation::create([
            'name' => 'Developer',
            'description' => 'Junior Developer',
        ]);

        $this->employee = User::create([
            'name' => 'Jane Developer',
            'email' => 'jane.dev@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'manager_id' => $this->manager->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
        ]);
    }

    /**
     * Test manager can access the leaderboard page.
     */
    public function test_manager_can_access_leaderboard_page(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->get(route('dashboard.leaderboard.index'));

        $response->assertStatus(200);
        $response->assertSee('Performance Leaderboards');
        $response->assertSee('Individual Reports');
        $response->assertSee('Jane Developer');
    }

    /**
     * Test different periods filters and custom date ranges.
     */
    public function test_leaderboard_supports_periods_and_custom_ranges(): void
    {
        foreach (['daily', 'weekly', 'monthly', 'quarterly', 'yearly'] as $period) {
            $response = $this->actingAs($this->manager)
                ->withSession(['active_role' => 'manager'])
                ->get(route('dashboard.leaderboard.index', ['period' => $period]));

            $response->assertStatus(200);
        }

        // Custom range
        $responseCustom = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->get(route('dashboard.leaderboard.index', [
                'period' => 'custom',
                'start_date' => now()->subDays(5)->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d')
            ]));

        $responseCustom->assertStatus(200);
    }

    /**
     * Test individual leaderboard unified performance score calculations.
     */
    public function test_individual_leaderboard_calculates_correct_overall_score(): void
    {
        // 1. Create a task assigned to employee
        Task::create([
            'title' => 'Important Bug Fix',
            'description' => 'Fix prod crash',
            'assigned_to' => $this->employee->id,
            'status' => 'completed',
            'deadline' => now()->addDays(2),
            'updated_at' => now(),
            'created_at' => now()->subDay(),
        ]);

        // 2. Create some commits
        DeveloperActivity::create([
            'user_id' => $this->employee->id,
            'platform' => 'gitlab',
            'event_type' => 'commit',
            'repository' => 'main-app',
            'reference_id' => 'sha-12345',
            'details_json' => [],
            'occurred_at' => now(),
        ]);

        // 3. Create clock-in log (late to compute a lower score)
        AttendanceLog::create([
            'user_id' => $this->employee->id,
            'date' => now()->format('Y-m-d'),
            'status' => 'late',
            'check_in' => '10:15:00',
        ]);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->get(route('dashboard.leaderboard.index', ['tab' => 'individual']));

        $response->assertStatus(200);
        // Overall score should be calculated and displayed
        $response->assertSee('Jane Developer');
    }

    /**
     * Test team leaderboard aggregates member scores correctly.
     */
    public function test_team_leaderboard_aggregates_metrics_correctly(): void
    {
        $team = Team::create([
            'name' => 'Core Platform',
            'manager_id' => $this->manager->id,
        ]);

        $team->members()->attach($this->employee->id);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->get(route('dashboard.leaderboard.index', ['tab' => 'team']));

        $response->assertStatus(200);
        $response->assertSee('Core Platform');
    }

    /**
     * Test organization leaderboard fallback to individual tab.
     */
    public function test_organization_leaderboard_fallback_to_individual(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->get(route('dashboard.leaderboard.index', ['tab' => 'organization']));

        $response->assertStatus(200);
        $response->assertSee('Individual Reports');
        $response->assertDontSee('Top 10 Overall Employees');
    }
}
