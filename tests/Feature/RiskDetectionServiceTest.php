<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\RiskAlert;
use App\Models\Notification;
use App\Services\RiskDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RiskDetectionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_detect_burnout_risk_for_excessive_hours(): void
    {
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

        $task = Task::create([
            'title' => 'API Integration',
            'status' => 'in_progress',
            'assigned_to' => $employee->id,
        ]);

        // Log 55 hours in time entries for the last 7 days
        TimeEntry::create([
            'task_id' => $task->id,
            'user_id' => $employee->id,
            'started_at' => Carbon::now()->subDays(2)->hour(8)->minute(0),
            'stopped_at' => Carbon::now()->subDays(2)->hour(8)->minute(0)->addHours(55),
            'duration_seconds' => 55 * 3600,
        ]);

        $riskService = app(RiskDetectionService::class);
        $riskService->detectUserRisks($employee->id);

        $this->assertDatabaseHas('risk_alerts', [
            'employee_id' => $employee->id,
            'risk_type' => 'burnout',
            'risk_level' => 'high',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $manager->id,
            'type' => 'burnout_risk',
        ]);
    }

    public function test_can_detect_deadline_risk_for_overdue_tasks(): void
    {
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

        // Create 2 overdue active tasks
        Task::create([
            'title' => 'Task 1',
            'status' => 'in_progress',
            'deadline' => Carbon::now()->subDays(2),
            'assigned_to' => $employee->id,
        ]);

        Task::create([
            'title' => 'Task 2',
            'status' => 'pending',
            'deadline' => Carbon::now()->subDays(1),
            'assigned_to' => $employee->id,
        ]);

        $riskService = app(RiskDetectionService::class);
        $riskService->detectUserRisks($employee->id);

        $this->assertDatabaseHas('risk_alerts', [
            'employee_id' => $employee->id,
            'risk_type' => 'deadline',
            'risk_level' => 'high',
        ]);
    }
}
