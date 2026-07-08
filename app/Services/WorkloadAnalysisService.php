<?php

namespace App\Services;

use App\Models\User;
use App\Models\Task;
use App\Models\TimeEntry;

class WorkloadAnalysisService
{
    /**
     * Analyze workload of employees in a manager's team.
     *
     * @param int $managerId
     * @param int|null $perPage
     * @return array
     */
    public function analyzeWorkload(int $managerId, ?int $perPage = null): array
    {
        $employeesQuery = User::where('manager_id', $managerId)->where('role', 'employee');
        $employees = $perPage ? $employeesQuery->paginate($perPage) : $employeesQuery->get();
        $employeeIds = $employees->pluck('id');

        // Fetch task counts in bulk grouped by assigned employee
        $taskStats = Task::whereIn('assigned_to', $employeeIds)
            ->selectRaw('assigned_to, 
                SUM(case when status in ("pending", "in_progress") then 1 else 0 end) as active_count,
                SUM(case when status = "completed" then 1 else 0 end) as completed_count,
                COUNT(*) as total_count')
            ->groupBy('assigned_to')
            ->get()
            ->keyBy('assigned_to');

        // Fetch average task durations in bulk grouped by user
        $avgDurations = TimeEntry::whereIn('user_id', $employeeIds)
            ->groupBy('user_id')
            ->selectRaw('user_id, AVG(duration_seconds) as avg_duration')
            ->pluck('avg_duration', 'user_id');

        $teamData = [];

        foreach ($employees as $employee) {
            $stats = $taskStats->get($employee->id);
            $activeTasks = $stats ? (int)$stats->active_count : 0;
            $completedTasks = $stats ? (int)$stats->completed_count : 0;
            $totalTasks = $stats ? (int)$stats->total_count : 0;

            $avgDurationSeconds = $avgDurations->get($employee->id, 0);
            $avgDurationHours = round($avgDurationSeconds / 3600, 1);

            // Classification
            if ($activeTasks > 4) {
                $status = 'Overloaded';
                $badgeClass = 'bg-red-50 text-red-700 dark:bg-red-950/20 dark:text-red-400 border-red-200 dark:border-red-800';
            } elseif ($activeTasks <= 1) {
                $status = 'Underutilized';
                $badgeClass = 'bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400 border-amber-200 dark:border-amber-800';
            } else {
                $status = 'Balanced';
                $badgeClass = 'bg-green-50 text-green-700 dark:bg-green-950/20 dark:text-green-400 border-green-200 dark:border-green-800';
            }

            $teamData[] = [
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'active_tasks' => $activeTasks,
                'completed_tasks' => $completedTasks,
                'total_tasks' => $totalTasks,
                'avg_task_duration' => $avgDurationHours,
                'status' => $status,
                'badge_class' => $badgeClass
            ];
        }

        $recommendations = $this->getBalancingRecommendations($managerId);

        return [
            'team_workload' => $teamData,
            'recommendations' => $recommendations,
            'paginator' => $perPage ? $employees : null
        ];
    }

    /**
     * Algorithmic balancing recommendation logic using database subqueries.
     */
    protected function getBalancingRecommendations(int $managerId): array
    {
        $overloaded = User::where('manager_id', $managerId)
            ->where('role', 'employee')
            ->whereHas('tasks', function($q) {
                $q->whereIn('status', ['pending', 'in_progress']);
            }, '>', 4)
            ->take(5)
            ->get();

        $underutilized = User::where('manager_id', $managerId)
            ->where('role', 'employee')
            ->where(function($query) {
                $query->whereDoesntHave('tasks', function($q) {
                    $q->whereIn('status', ['pending', 'in_progress']);
                })->orWhereHas('tasks', function($q) {
                    $q->whereIn('status', ['pending', 'in_progress']);
                }, '<=', 1);
            })
            ->take(5)
            ->get();

        $recommendations = [];
        foreach ($overloaded as $over) {
            $activeTasksCount = Task::where('assigned_to', $over->id)
                ->whereIn('status', ['pending', 'in_progress'])
                ->count();

            if ($underutilized->isNotEmpty()) {
                $under = $underutilized->shift();
                $moveCount = max(1, $activeTasksCount - 3);
                $recommendations[] = "Move {$moveCount} active task(s) from {$over->name} to {$under->name} to balance the team workload.";
            } else {
                $recommendations[] = "{$over->name} is currently overloaded. Consider delegating new tasks to other balanced members or adjusting upcoming deadlines.";
            }
        }

        if (empty($recommendations)) {
            $recommendations[] = "Workload is currently evenly distributed across your team.";
        }

        return $recommendations;
    }
}
