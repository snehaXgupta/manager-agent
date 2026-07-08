<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AttendanceLog;
use App\Models\LeaveRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceLeaveFeatureTest extends TestCase
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
     * Test employee clockout early exit tracking.
     */
    public function test_employee_early_exit_clockout(): void
    {
        // 1. Clock in first
        Carbon::setTestNow(Carbon::create(2026, 6, 22, 9, 0, 0)); // 9:00 AM
        $this->actingAs($this->employee)
            ->post(route('employee.clock-in'));

        $this->assertDatabaseHas('attendance_logs', [
            'user_id' => $this->employee->id,
            'date' => '2026-06-22 00:00:00',
            'status' => 'present',
        ]);

        // 2. Clock out early (e.g. 4:00 PM / 16:00:00)
        Carbon::setTestNow(Carbon::create(2026, 6, 22, 16, 0, 0)); // 4:00 PM
        $response = $this->actingAs($this->employee)
            ->post(route('employee.clock-out'));

        $response->assertRedirect();
        
        $this->assertDatabaseHas('attendance_logs', [
            'user_id' => $this->employee->id,
            'date' => '2026-06-22 00:00:00',
            'is_early_exit' => true,
        ]);

        Carbon::setTestNow(); // reset
    }

    /**
     * Test employee regular clockout (not early exit).
     */
    public function test_employee_regular_clockout(): void
    {
        // 1. Clock in first
        Carbon::setTestNow(Carbon::create(2026, 6, 22, 9, 0, 0)); // 9:00 AM
        $this->actingAs($this->employee)
            ->post(route('employee.clock-in'));

        // 2. Clock out after 5:00 PM (e.g. 5:30 PM / 17:30:00)
        Carbon::setTestNow(Carbon::create(2026, 6, 22, 17, 30, 0)); // 5:30 PM
        $response = $this->actingAs($this->employee)
            ->post(route('employee.clock-out'));

        $response->assertRedirect();
        
        $this->assertDatabaseHas('attendance_logs', [
            'user_id' => $this->employee->id,
            'date' => '2026-06-22 00:00:00',
            'is_early_exit' => false,
        ]);

        Carbon::setTestNow(); // reset
    }

    /**
     * Test employee can submit a leave request.
     */
    public function test_employee_can_request_leave(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 22, 9, 0, 0));

        $response = $this->actingAs($this->employee)
            ->post(route('employee.leaves.store'), [
                'start_date' => '2026-06-23',
                'end_date' => '2026-06-25',
                'type' => 'vacation',
                'reason' => 'Family trip.',
            ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $this->employee->id,
            'start_date' => '2026-06-23 00:00:00',
            'end_date' => '2026-06-25 00:00:00',
            'type' => 'vacation',
            'status' => 'pending',
        ]);

        Carbon::setTestNow();
    }

    /**
     * Test employee cannot submit overlapping leaves.
     */
    public function test_employee_cannot_submit_overlapping_leaves(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 22, 9, 0, 0));

        // Create first leave request
        LeaveRequest::create([
            'user_id' => $this->employee->id,
            'start_date' => '2026-06-23',
            'end_date' => '2026-06-25',
            'type' => 'sick',
            'status' => 'pending',
        ]);

        // Submit overlapping leave request
        $response = $this->actingAs($this->employee)
            ->post(route('employee.leaves.store'), [
                'start_date' => '2026-06-24',
                'end_date' => '2026-06-26',
                'type' => 'casual',
                'reason' => 'Overlapping.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        
        $this->assertDatabaseMissing('leave_requests', [
            'user_id' => $this->employee->id,
            'start_date' => '2026-06-24 00:00:00',
            'end_date' => '2026-06-26 00:00:00',
            'type' => 'casual',
        ]);

        Carbon::setTestNow();
    }

    /**
     * Test manager can view, approve, and reject leaves.
     */
    public function test_manager_can_review_leaves(): void
    {
        $leave = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'start_date' => '2026-06-23',
            'end_date' => '2026-06-25',
            'type' => 'sick',
            'status' => 'pending',
        ]);

        // Approve leave
        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->post(route('dashboard.leaves.approve', $leave->id));

        $response->assertRedirect();
        
        $this->assertDatabaseHas('leave_requests', [
            'id' => $leave->id,
            'status' => 'approved',
            'approved_by' => $this->manager->id,
        ]);

        // Change status to pending for reject test
        $leave->update(['status' => 'pending', 'approved_by' => null]);

        // Reject leave
        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->post(route('dashboard.leaves.reject', $leave->id));

        $response->assertRedirect();
        
        $this->assertDatabaseHas('leave_requests', [
            'id' => $leave->id,
            'status' => 'rejected',
            'approved_by' => $this->manager->id,
        ]);
    }

    /**
     * Test metrics calculation logic in controller.
     */
    public function test_attendance_metrics_calculations(): void
    {
        // Lock time to Friday 2026-06-05 after working hours
        Carbon::setTestNow(Carbon::create(2026, 6, 5, 18, 0, 0));

        // Setup date range: Monday 2026-06-01 to Friday 2026-06-05 (5 workdays)
        $start = Carbon::create(2026, 6, 1);
        $end = Carbon::create(2026, 6, 5);

        // 1 day approved leave (excused workday)
        LeaveRequest::create([
            'user_id' => $this->employee->id,
            'start_date' => '2026-06-03',
            'end_date' => '2026-06-03',
            'type' => 'sick',
            'status' => 'approved',
            'approved_by' => $this->manager->id,
        ]);

        // Expected workdays = 5 workdays - 1 leave day = 4 days
        
        // 2 days present
        AttendanceLog::create([
            'user_id' => $this->employee->id,
            'date' => '2026-06-01',
            'check_in' => '09:00:00',
            'status' => 'present',
        ]);

        // 1 day late check-in
        AttendanceLog::create([
            'user_id' => $this->employee->id,
            'date' => '2026-06-02',
            'check_in' => '09:45:00',
            'status' => 'late',
        ]);

        // 1 day early exit (clocked out early)
        AttendanceLog::create([
            'user_id' => $this->employee->id,
            'date' => '2026-06-04',
            'check_in' => '09:00:00',
            'check_out' => '15:00:00',
            'status' => 'present',
            'is_early_exit' => true,
        ]);

        // Total present/late = 3 days (June 1, 2, 4)
        // Expected workdays = 4 days (June 1, 2, 4, 5)
        // Absent = 1 day (June 5)
        // Attendance % = 3 / 4 * 100 = 75%
        // Late Count = 1, Early Exits = 1, Absent Count = 1
        // Attendance Score = 100 - (1 * 5) - (1 * 15) - (1 * 5) = 75
        
        $response = $this->actingAs($this->employee)
            ->get(route('employee.attendance.index', [
                'month' => 6,
                'year' => 2026,
            ]));

        $response->assertStatus(200);
        
        $metrics = $response->viewData('metrics');
        $this->assertEquals(75, $metrics['attendance_score']);
        $this->assertEquals(75, $metrics['attendance_percentage']);
        $this->assertEquals(1, $metrics['late_days']);
        $this->assertEquals(1, $metrics['early_exits']);
        $this->assertEquals(1, $metrics['absent_days']);

        Carbon::setTestNow(); // reset
    }

    /**
     * Test manager dashboard today attendance filter.
     */
    public function test_manager_dashboard_today_attendance_filter(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 23, 12, 0, 0));

        // Create 2 direct report employees
        $emp1 = User::create([
            'name' => 'Emp One',
            'email' => 'emp1@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'manager_id' => $this->manager->id,
        ]);
        $emp2 = User::create([
            'name' => 'Emp Two',
            'email' => 'emp2@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'manager_id' => $this->manager->id,
        ]);

        // emp1 is present
        AttendanceLog::create([
            'user_id' => $emp1->id,
            'date' => '2026-06-23',
            'check_in' => '09:00:00',
            'status' => 'present',
        ]);

        // emp2 is late
        AttendanceLog::create([
            'user_id' => $emp2->id,
            'date' => '2026-06-23',
            'check_in' => '09:45:00',
            'status' => 'late',
        ]);

        // emp3 is absent (no log)
        $emp3 = User::create([
            'name' => 'Emp Three',
            'email' => 'emp3@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'manager_id' => $this->manager->id,
        ]);

        // Call dashboard with duration=today
        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->get(route('dashboard.index', ['duration' => 'today']));

        $response->assertStatus(200);

        // Assert view variables
        $response->assertViewHas('presentCount', 1);
        $response->assertViewHas('lateCount', 1);
        $response->assertViewHas('absentCountRange', 2);
        $response->assertViewHas('duration', 'today');

        Carbon::setTestNow(); // reset
    }

    public function test_manager_can_clock_in_employee_on_their_behalf(): void
    {
        $employee = User::create([
            'name' => 'Test Employee Clockin',
            'email' => 'test.clockin@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'manager_id' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->post(route('dashboard.employees.clock-in', $employee->id));

        $response->assertRedirect();
        
        $log = AttendanceLog::where('user_id', $employee->id)->first();
        $this->assertNotNull($log);
        $this->assertNotNull($log->check_in);
    }

    public function test_manager_can_clock_out_employee_on_their_behalf(): void
    {
        $employee = User::create([
            'name' => 'Test Employee Clockout',
            'email' => 'test.clockout@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'manager_id' => $this->manager->id,
        ]);

        AttendanceLog::create([
            'user_id' => $employee->id,
            'date' => Carbon::today()->toDateString(),
            'check_in' => '09:00:00',
            'status' => 'present',
        ]);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->post(route('dashboard.employees.clock-out', $employee->id));

        $response->assertRedirect();
        
        $log = AttendanceLog::where('user_id', $employee->id)
            ->whereDate('date', Carbon::today()->toDateString())
            ->first();

        $this->assertNotNull($log->check_out);
    }
}
