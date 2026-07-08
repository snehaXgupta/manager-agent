<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\AttendanceLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ManagerScoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $managerId = 2; // Amelia Brand
        
        // 1. Fetch direct reports of Amelia Brand
        $employeeIds = User::where('manager_id', $managerId)
            ->where('role', 'employee')
            ->pluck('id')
            ->toArray();

        if (empty($employeeIds)) {
            $this->command->error("No employees found for Manager Amelia Brand.");
            return;
        }

        // Today is dynamic
        $today = Carbon::today();
        $todayStr = $today->toDateString();

        // Clear existing attendance logs for today for these employees
        AttendanceLog::whereIn('user_id', $employeeIds)
            ->where('date', $todayStr)
            ->delete();

        // 2. Seed exactly 8,001 present logs and the rest absent
        $logsToInsert = [];
        $totalPresent = 25;

        for ($i = 0; $i < count($employeeIds); $i++) {
            $status = ($i < $totalPresent) ? 'present' : 'absent';
            $logsToInsert[] = [
                'user_id' => $employeeIds[$i],
                'date' => $todayStr,
                'status' => $status,
                'check_in' => ($status === 'present') ? '09:00:00' : '00:00:00',
                'check_out' => ($status === 'present') ? '17:00:00' : '00:00:00',
                'is_early_exit' => false,
                'created_at' => Carbon::parse($todayStr . ' 09:00:00'),
                'updated_at' => Carbon::parse($todayStr . ' 17:00:00'),
            ];
        }

        // Chunk bulk insert
        foreach (array_chunk($logsToInsert, 1000) as $chunk) {
            AttendanceLog::insert($chunk);
        }

        // 3. Reset tasks and time entries for current week to target a manager score of exactly 25.0%
        // Delete all tasks and time entries for these employees
        Task::whereIn('assigned_to', $employeeIds)->delete();
        TimeEntry::whereIn('user_id', $employeeIds)->delete();

        // Formula: Manager Score = 40% Completion Rate + 20% Deadline Adherence + 20% Productivity + 20% Consistency
        // Target: 25.0%
        // Set: Completion Rate = 62.5% (625 completed / 1000 total)
        // Set: Deadline Adherence = 0.0% (all completed are late)
        // Set: Productivity = 0.0% (no time entries)
        // Set: Consistency = 0.0% (no time entries)
        // Manager Score = 0.40 * 62.5% + 0.20 * 0% + 0.20 * 0% + 0.20 * 0% = 25.0%
        
        $tasksToInsert = [];
        // 625 completed late tasks
        for ($i = 0; $i < 625; $i++) {
            $empId = $employeeIds[$i % count($employeeIds)];
            $tasksToInsert[] = [
                'title' => 'Late Task ' . $i,
                'description' => 'Completed late to adjust score.',
                'status' => 'completed',
                'deadline' => $today->copy()->subDays(5)->hour(12)->minute(0)->second(0),
                'assigned_to' => $empId,
                'created_at' => $today->copy()->subDays(6)->hour(9)->minute(0)->second(0),
                'updated_at' => $today->copy()->subDays(4)->hour(12)->minute(0)->second(0), // updated after deadline -> late
            ];
        }

        // 375 pending tasks
        for ($i = 625; $i < 1000; $i++) {
            $empId = $employeeIds[$i % count($employeeIds)];
            $tasksToInsert[] = [
                'title' => 'Pending Task ' . $i,
                'description' => 'Pending task to adjust score.',
                'status' => 'pending',
                'deadline' => $today->copy()->addDays(1)->hour(12)->minute(0)->second(0),
                'assigned_to' => $empId,
                'created_at' => $today->copy()->subDays(6)->hour(9)->minute(0)->second(0),
                'updated_at' => $today->copy()->subDays(6)->hour(9)->minute(0)->second(0),
            ];
        }

        foreach (array_chunk($tasksToInsert, 500) as $chunk) {
            Task::insert($chunk);
        }
    }
}
