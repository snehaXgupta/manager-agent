<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\Task;
use App\Models\TimeEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EmployeeDashboardController extends Controller
{
    /**
     * Display the employee dashboard.
     */
    public function index()
    {
        $employee = auth()->user();

        // Get tasks sorted by active timer (in progress) first, then pending, then completed
        $tasks = Task::where('assigned_to', $employee->id)
            ->orderByRaw("CASE 
                WHEN status = 'in_progress' THEN 1 
                WHEN status = 'pending' THEN 2 
                ELSE 3 
            END")
            ->orderBy('deadline', 'asc')
            ->get();

        // Get recent time entries
        $timeEntries = TimeEntry::where('user_id', $employee->id)
            ->with('task')
            ->orderBy('started_at', 'desc')
            ->take(10)
            ->get();

        // Active timer (if any)
        $activeTimer = TimeEntry::where('user_id', $employee->id)
            ->whereNull('stopped_at')
            ->with('task')
            ->first();

        // Get attendance status for today
        $attendance = AttendanceLog::where('user_id', $employee->id)
            ->whereDate('date', Carbon::today())
            ->first();

        // Get active unresolved risk alerts for the employee
        $activeAlerts = \App\Models\RiskAlert::where('employee_id', $employee->id)
            ->where('is_resolved', false)
            ->get();

        return view('employee.dashboard', compact('employee', 'tasks', 'timeEntries', 'activeTimer', 'attendance', 'activeAlerts'));
    }

    /**
     * Clock In for today.
     */
    public function clockIn(Request $request)
    {
        $employee = auth()->user();
        $todayStr = Carbon::today()->toDateString();

        $existing = AttendanceLog::where('user_id', $employee->id)
            ->whereDate('date', $todayStr)
            ->first();

        if ($existing) {
            return redirect()->back()->with('error', 'You have already clocked in today.');
        }

        // Determine if late (e.g. after 09:30:00)
        $now = Carbon::now();
        $status = 'present';
        
        // Let's check if current time is after 9:30 AM
        if ($now->hour > 9 || ($now->hour == 9 && $now->minute > 30)) {
            $status = 'late';
        }

        AttendanceLog::create([
            'user_id' => $employee->id,
            'date' => $todayStr,
            'check_in' => $now->toTimeString(),
            'status' => $status,
        ]);

        return redirect()->back()->with('success', 'Clocked in successfully. Status: ' . ucfirst($status));
    }

    /**
     * Clock Out for today.
     */
    public function clockOut(Request $request)
    {
        $employee = auth()->user();
        $todayStr = Carbon::today()->toDateString();

        $attendance = AttendanceLog::where('user_id', $employee->id)
            ->whereDate('date', $todayStr)
            ->first();

        if (!$attendance) {
            return redirect()->back()->with('error', 'You must Clock In before you can Clock Out.');
        }

        if ($attendance->check_out) {
            return redirect()->back()->with('error', 'You have already clocked out today.');
        }

        // Stop any running task timers first
        $activeTimer = TimeEntry::where('user_id', $employee->id)
            ->whereNull('stopped_at')
            ->first();

        if ($activeTimer) {
            $stoppedAt = Carbon::now();
            $durationSeconds = (int) abs($stoppedAt->diffInSeconds(Carbon::parse($activeTimer->started_at)));
            $activeTimer->update([
                'stopped_at' => $stoppedAt,
                'duration_seconds' => $durationSeconds,
            ]);
        }

        $now = Carbon::now();
        $isEarlyExit = $now->hour < 17;

        $attendance->update([
            'check_out' => $now->toTimeString(),
            'is_early_exit' => $isEarlyExit,
        ]);

        $message = 'Clocked out successfully.';
        if ($isEarlyExit) {
            $message .= ' Note: Early checkout recorded.';
        }
        if ($activeTimer) {
            $message .= ' Running task timer was also stopped.';
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Mark a task as completed.
     */
    public function completeTask($id)
    {
        $employee = auth()->user();
        $task = Task::where('id', $id)
            ->where('assigned_to', $employee->id)
            ->firstOrFail();

        if ($task->status === 'completed') {
            return redirect()->back()->with('warning', 'Task is already completed.');
        }

        // Stop any running timer for this specific task
        $activeTimer = TimeEntry::where('user_id', $employee->id)
            ->where('task_id', $task->id)
            ->whereNull('stopped_at')
            ->first();

        if ($activeTimer) {
            $stoppedAt = Carbon::now();
            $durationSeconds = (int) abs($stoppedAt->diffInSeconds(Carbon::parse($activeTimer->started_at)));
            $activeTimer->update([
                'stopped_at' => $stoppedAt,
                'duration_seconds' => $durationSeconds,
            ]);
        }

        $task->update(['status' => 'completed']);

        $message = 'Task marked as completed successfully.';
        if ($activeTimer) {
            $message .= ' Active task timer was also stopped.';
        }

        return redirect()->back()->with('success', $message);
    }
}
