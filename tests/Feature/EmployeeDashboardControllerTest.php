<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\AttendanceLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EmployeeDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = User::create([
            'name' => 'John Dev',
            'email' => 'john.dev@company.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
        ]);
    }

    public function test_employee_can_clock_in_successfully(): void
    {
        $response = $this->actingAs($this->employee)
            ->withSession(['active_role' => 'employee'])
            ->post(route('employee.clock-in'));

        $response->assertRedirect();
        
        $log = AttendanceLog::where('user_id', $this->employee->id)->first();
        $this->assertNotNull($log);
        $this->assertNotNull($log->check_in);
    }

    public function test_employee_can_clock_out_successfully(): void
    {
        AttendanceLog::create([
            'user_id' => $this->employee->id,
            'date' => Carbon::today()->toDateString(),
            'check_in' => '09:00:00',
            'status' => 'present',
        ]);

        $response = $this->actingAs($this->employee)
            ->withSession(['active_role' => 'employee'])
            ->post(route('employee.clock-out'));

        $response->assertRedirect();
        
        $log = AttendanceLog::where('user_id', $this->employee->id)->first();
        $this->assertNotNull($log->check_out);
    }

    public function test_employee_can_mark_task_completed(): void
    {
        $task = Task::create([
            'title' => 'Complete documentation',
            'status' => 'pending',
            'assigned_to' => $this->employee->id,
        ]);

        // Start active timer on task
        $timeEntry = TimeEntry::create([
            'task_id' => $task->id,
            'user_id' => $this->employee->id,
            'started_at' => Carbon::now()->subHour(),
        ]);

        $response = $this->actingAs($this->employee)
            ->withSession(['active_role' => 'employee'])
            ->post(route('employee.tasks.complete', $task->id));

        $response->assertRedirect();

        $task->refresh();
        $this->assertEquals('completed', $task->status);

        $timeEntry->refresh();
        $this->assertNotNull($timeEntry->stopped_at);
        $this->assertGreaterThanOrEqual(3600, $timeEntry->duration_seconds);
    }
}
