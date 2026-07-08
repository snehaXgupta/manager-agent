<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\PerformanceAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PerformanceAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new PerformanceAnalyticsService();
    }

    public function test_calculate_team_metrics_with_empty_team(): void
    {
        $manager = User::create([
            'name' => 'Sarah',
            'email' => 'sarah@example.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
        ]);

        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $metrics = $this->analyticsService->calculateTeamMetrics($manager->id, $startDate, $endDate);

        $this->assertEquals(0, $metrics['team_size']);
        $this->assertEquals(100.0, $metrics['task_completion_rate']);
        $this->assertEquals(100.0, $metrics['deadline_adherence_rate']);
        $this->assertEquals(0.0, $metrics['productivity_score']);
        $this->assertEquals(0.0, $metrics['consistency_score']);
        $this->assertEquals(40.0, $metrics['manager_score']);
        $this->assertEquals(100.0, $metrics['developer_score']);
        $this->assertEquals(100.0, $metrics['code_quality_score']);
        $this->assertEquals(100.0, $metrics['reviews_score']);
        $this->assertEquals(100.0, $metrics['delivery_speed_score']);
    }

    public function test_calculate_team_metrics_with_mock_data(): void
    {
        // 1. Create Team Structure
        $manager = User::create([
            'name' => 'Sarah',
            'email' => 'sarah@example.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
        ]);

        $employee = User::create([
            'name' => 'Rahul',
            'email' => 'rahul@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'manager_id' => $manager->id,
        ]);

        // Start Date: 7 days ago (Monday), End Date: today (Sunday)
        // Let's set dates manually so they are weekend-independent and completely reproducible.
        // We'll set a range of 5 weekdays: Mon June 1 to Fri June 5, 2026.
        $startDate = Carbon::parse('2026-06-01 00:00:00');
        $endDate = Carbon::parse('2026-06-07 23:59:59'); // includes the weekend

        // 2. Create Tasks for Employee
        // Task 1: Completed on time
        $task1 = new Task([
            'title' => 'Task One',
            'status' => 'completed',
            'deadline' => Carbon::parse('2026-06-04 18:00:00'),
            'assigned_to' => $employee->id,
        ]);
        $task1->timestamps = false;
        $task1->created_at = Carbon::parse('2026-06-01 09:00:00');
        $task1->updated_at = Carbon::parse('2026-06-03 17:00:00'); // Completed before deadline
        $task1->save();

        // Task 2: Completed late
        $task2 = new Task([
            'title' => 'Task Two',
            'status' => 'completed',
            'deadline' => Carbon::parse('2026-06-03 18:00:00'),
            'assigned_to' => $employee->id,
        ]);
        $task2->timestamps = false;
        $task2->created_at = Carbon::parse('2026-06-01 09:00:00');
        $task2->updated_at = Carbon::parse('2026-06-05 17:00:00'); // Completed after deadline
        $task2->save();

        // Task 3: Pending
        $task3 = new Task([
            'title' => 'Task Three',
            'status' => 'pending',
            'deadline' => Carbon::parse('2026-06-08 18:00:00'),
            'assigned_to' => $employee->id,
        ]);
        $task3->timestamps = false;
        $task3->created_at = Carbon::parse('2026-06-04 09:00:00');
        $task3->updated_at = Carbon::parse('2026-06-04 09:00:00');
        $task3->save();

        // 3. Create Time Entries
        // Expected workdays in range (June 1 to June 7): 5 weekdays.
        // Expected hours per employee = 5 days * 8 hours = 40 expected hours.
        // Let's seed 6 hours of work daily for the 5 weekdays (June 1, 2, 3, 4, 5).
        // Total actual hours = 5 days * 6 hours = 30 hours.
        // Productivity score should be: (30 / 40) * 100 = 75%.
        // Logged hours are exactly 6 hours every workday -> Standard deviation is 0.
        // Consistency score should be: 100 * (1 - 0) = 100%.
        $workdays = ['2026-06-01', '2026-06-02', '2026-06-03', '2026-06-04', '2026-06-05'];
        foreach ($workdays as $dayStr) {
            $date = Carbon::parse($dayStr);
            TimeEntry::create([
                'task_id' => $task1->id,
                'user_id' => $employee->id,
                'started_at' => $date->copy()->hour(9)->minute(0),
                'stopped_at' => $date->copy()->hour(15)->minute(0), // 6 hours
                'duration_seconds' => 6 * 3600,
            ]);
        }

        // 4. Seed Git activity to achieve 100.00 scores for Developer
        // 10 commits for code quality score = 80 + 10 * 2 = 100
        for ($i = 0; $i < 10; $i++) {
            \App\Models\DeveloperActivity::create([
                'user_id' => $employee->id,
                'platform' => 'github',
                'event_type' => 'commit',
                'repository' => 'acme/webapp',
                'reference_id' => 'commit-' . $i,
                'details_json' => ['message' => 'Committing ' . $i],
                'occurred_at' => $startDate->copy()->addHours($i),
            ]);
        }

        // 3 reviews for reviews score = 70 + 3 * 10 = 100
        for ($i = 0; $i < 3; $i++) {
            \App\Models\DeveloperActivity::create([
                'user_id' => $employee->id,
                'platform' => 'github',
                'event_type' => 'review_submitted',
                'repository' => 'acme/webapp',
                'reference_id' => 'review-' . $i,
                'details_json' => ['state' => 'approved'],
                'occurred_at' => $startDate->copy()->addHours($i + 12),
            ]);
        }

        // 5. Calculate metrics
        $metrics = $this->analyticsService->calculateTeamMetrics($manager->id, $startDate, $endDate);

        // 5. Assertions
        // Completion Rate: 2 completed tasks out of 3 total = 66.67%
        $this->assertEquals(66.67, $metrics['task_completion_rate']);

        // Deadline Adherence Rate: 1 of 2 completed tasks was on time = 50.00%
        $this->assertEquals(50.00, $metrics['deadline_adherence_rate']);

        // Productivity Score: 30 hours logged / 40 expected hours = 75.00%
        $this->assertEquals(75.00, $metrics['productivity_score']);

        // Consistency Score: daily hours are [6,6,6,6,6] -> standard deviation is 0 -> 100.00%
        $this->assertEquals(100.00, $metrics['consistency_score']);

        // Manager Score Formula:
        // Score = 0.40 * 66.67 + 0.20 * 50.00 + 0.20 * 75.00 + 0.20 * 100.00
        //       = 26.668 + 10.00 + 15.00 + 20.00
        //       = 71.67
        $this->assertEquals(71.67, $metrics['manager_score']);

        // Developer Score Formula:
        // Score = 0.40 * 66.67 + 0.20 * 100.00 + 0.20 * 100.00 + 0.20 * 50.00
        //       = 26.668 + 20.00 + 20.00 + 10.00
        //       = 76.67
        $this->assertEquals(76.67, $metrics['developer_score']);
        $this->assertEquals(100.00, $metrics['code_quality_score']);
        $this->assertEquals(100.00, $metrics['reviews_score']);
        $this->assertEquals(50.00, $metrics['delivery_speed_score']);
    }
}
