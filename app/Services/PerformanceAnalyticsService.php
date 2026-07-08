<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PerformanceAnalyticsService
{
    /**
     * Compute all performance metrics for a manager's team.
     *
     * @param int $managerId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function calculateTeamMetrics(int $managerId, Carbon $startDate, Carbon $endDate): array
    {
        $teamUserSubquery = User::select('id')->where('manager_id', $managerId);
        return $this->calculateMetricsForUserIds($teamUserSubquery, $startDate, $endDate);
    }

    /**
     * Compute performance metrics for a single employee.
     *
     * @param int $userId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function calculateUserMetrics(int $userId, Carbon $startDate, Carbon $endDate): array
    {
        return $this->calculateMetricsForUserIds(collect([$userId]), $startDate, $endDate);
    }

    /**
     * Calculate metrics for a collection of user IDs or a query builder.
     */
    public function calculateMetricsForUserIds($teamUserIds, Carbon $startDate, Carbon $endDate): array
    {
        $isCollection = $teamUserIds instanceof Collection;
        $isEmpty = $isCollection ? $teamUserIds->isEmpty() : !$teamUserIds->exists();

        if ($isEmpty) {
            return [
                'team_size' => 0,
                'task_completion_rate' => 100.0,
                'deadline_adherence_rate' => 100.0,
                'productivity_score' => 0.0,
                'consistency_score' => 0.0,
                'manager_score' => 40.0, // Default base score (40% of 100% completion & adherence)
                'developer_score' => 100.0,
                'code_quality_score' => 100.0,
                'reviews_score' => 100.0,
                'delivery_speed_score' => 100.0,
                'metrics_breakdown' => [
                    'total_assigned_tasks' => 0,
                    'completed_tasks' => 0,
                    'completed_on_time_tasks' => 0,
                    'total_hours_logged' => 0.0,
                    'expected_hours' => 0.0,
                ]
            ];
        }

        $teamSize = $isCollection ? $teamUserIds->count() : $teamUserIds->count();

        // 1. Team Task Completion Rate (C)
        // Tasks assigned to team members created on or before the end date.
        $tasksQuery = Task::whereIn('assigned_to', $teamUserIds)
            ->where('created_at', '<=', $endDate);

        $totalTasks = $tasksQuery->count();
        
        $completedTasksCount = (clone $tasksQuery)
            ->where('status', 'completed')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();

        $taskCompletionRate = $totalTasks > 0 ? ($completedTasksCount / $totalTasks) * 100 : 100.0;

        // 2. Deadline Adherence Rate (A)
        // Ratio of completed tasks in this period that were finished on or before the deadline.
        $completedOnTimeCount = (clone $tasksQuery)
            ->where('status', 'completed')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->where(function ($query) {
                $query->whereNull('deadline')
                    ->orWhereColumn('updated_at', '<=', 'deadline');
            })
            ->count();

        $deadlineAdherenceRate = $completedTasksCount > 0 
            ? ($completedOnTimeCount / $completedTasksCount) * 100 
            : 100.0;

        // 3. Team Productivity Score (P)
        // Calculate expected weekdays (Mon-Fri) in the range
        $workdays = 0;
        $tempDate = clone $startDate;
        while ($tempDate <= $endDate) {
            if (!$tempDate->isWeekend()) {
                $workdays++;
            }
            $tempDate->addDay();
        }

        $expectedHoursPerEmployee = $workdays * 8;
        $totalExpectedHours = $expectedHoursPerEmployee * $teamUserIds->count();

        // Total seconds logged by team during the period
        $totalSecondsLogged = TimeEntry::whereIn('user_id', $teamUserIds)
            ->whereBetween('started_at', [$startDate, $endDate])
            ->sum('duration_seconds');

        $totalHoursLogged = $totalSecondsLogged / 3600;
        
        $productivityScore = $totalExpectedHours > 0 
            ? ($totalHoursLogged / $totalExpectedHours) * 100 
            : 0.0;
        
        // Cap productivity score at 100%
        $productivityScore = min($productivityScore, 100.0);

        // 4. Team Consistency Score (S)
        // Group logged hours by day to calculate daily team work hours variance.
        $dailySeconds = TimeEntry::whereIn('user_id', $teamUserIds)
            ->whereBetween('started_at', [$startDate, $endDate])
            ->selectRaw('DATE(started_at) as entry_date, SUM(duration_seconds) as total_seconds')
            ->groupByRaw('DATE(started_at)')
            ->pluck('total_seconds', 'entry_date');

        $dailyHours = [];
        $tempDate = clone $startDate;
        while ($tempDate <= $endDate) {
            if (!$tempDate->isWeekend()) {
                $dateStr = $tempDate->toDateString();
                $secondsForDay = $dailySeconds->get($dateStr, 0);
                $dailyHours[] = $secondsForDay / 3600;
            }
            $tempDate->addDay();
        }

        $consistencyScore = $this->calculateConsistency($dailyHours);

        // 5. Manager Score Formula
        // Manager Score = 40% Completion Rate + 20% Deadline Adherence + 20% Productivity + 20% Consistency
        $managerScore = (0.40 * $taskCompletionRate) + 
                       (0.20 * $deadlineAdherenceRate) + 
                       (0.20 * $productivityScore) + 
                       (0.20 * $consistencyScore);

        // 6. Developer Score Formula using actual Git integration activities
        // Find Git commits & PR reviews in the period
        $commitsCount = \App\Models\DeveloperActivity::whereIn('user_id', $teamUserIds)
            ->where('event_type', 'commit')
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->count();

        $reviewsCount = \App\Models\DeveloperActivity::whereIn('user_id', $teamUserIds)
            ->where('event_type', 'review_submitted')
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->count();

        // Dynamically compute Code Quality score based on commit activity (starts at 80, increments per commit up to 100)
        $codeQualityScore = $commitsCount > 0 ? min(80.0 + ($commitsCount * 2.0), 100.0) : 80.0;

        // Dynamically compute Reviews score based on submitted PR reviews (starts at 70, increments by 10 per review up to 100)
        $reviewsScore = $reviewsCount > 0 ? min(70.0 + ($reviewsCount * 10.0), 100.0) : 70.0;

        $deliverySpeedScore = $deadlineAdherenceRate; // using deadline adherence as primary delivery speed metric
        
        $developerScore = (0.40 * $taskCompletionRate) + 
                          (0.20 * $codeQualityScore) + 
                          (0.20 * $reviewsScore) + 
                          (0.20 * $deliverySpeedScore);

        return [
            'team_size' => $teamUserIds->count(),
            'task_completion_rate' => round($taskCompletionRate, 2),
            'deadline_adherence_rate' => round($deadlineAdherenceRate, 2),
            'productivity_score' => round($productivityScore, 2),
            'consistency_score' => round($consistencyScore, 2),
            'manager_score' => round($managerScore, 2),
            'developer_score' => round($developerScore, 2),
            'code_quality_score' => round($codeQualityScore, 2),
            'reviews_score' => round($reviewsScore, 2),
            'delivery_speed_score' => round($deliverySpeedScore, 2),
            'metrics_breakdown' => [
                'total_assigned_tasks' => $totalTasks,
                'completed_tasks' => $completedTasksCount,
                'completed_on_time_tasks' => $completedOnTimeCount,
                'total_hours_logged' => round($totalHoursLogged, 2),
                'expected_hours' => round($totalExpectedHours, 2),
            ]
        ];
    }

    /**
     * Calculate Consistency score based on Coefficient of Variation (CV).
     * S = 100 * (1 - CV)
     *
     * @param array $dailyHours
     * @return float
     */
    public function calculateConsistency(array $dailyHours): float
    {
        $count = count($dailyHours);
        if ($count === 0) {
            return 100.0;
        }

        $mean = array_sum($dailyHours) / $count;
        if ($mean == 0) {
            return 0.0; // If they did no work, they aren't consistent
        }

        // Calculate variance
        $varianceSum = 0;
        foreach ($dailyHours as $hours) {
            $varianceSum += pow($hours - $mean, 2);
        }
        $variance = $varianceSum / $count;
        $stdDev = sqrt($variance);

        $cv = $stdDev / $mean;

        // Score: 100 * (1 - CV)
        $score = 100 * (1 - $cv);

        return max(0.0, min(100.0, $score));
    }
}
