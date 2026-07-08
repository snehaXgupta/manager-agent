<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\User;
use Illuminate\Support\Carbon;

class TeamHealthService
{
    protected $analyticsService;

    public function __construct(PerformanceAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Calculate health score and detailed metrics for a team.
     *
     * @param int $managerId
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return array
     */
    public function calculateTeamHealth(int $managerId, ?Carbon $startDate = null, ?Carbon $endDate = null, ?array $metrics = null): array
    {
        $endDate = $endDate ?? Carbon::now()->endOfDay();
        $startDate = $startDate ?? Carbon::now()->subDays(6)->startOfDay();

        $employeeQuery = User::where('manager_id', $managerId)->where('role', 'employee');
        $employeeCount = $employeeQuery->count();
        
        // 1. Fetch team metrics from analytics service
        $metrics = $metrics ?? $this->analyticsService->calculateTeamMetrics($managerId, $startDate, $endDate);

        // 2. Calculate Attendance Health
        $workdays = 0;
        $tempDate = clone $startDate;
        while ($tempDate <= $endDate) {
            if (!$tempDate->isWeekend()) {
                $workdays++;
            }
            $tempDate->addDay();
        }

        if ($employeeCount > 0) {
            $logsCount = AttendanceLog::whereIn('user_id', function ($query) use ($managerId) {
                    $query->select('id')->from('users')->where('manager_id', $managerId)->where('role', 'employee');
                })
                ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                ->whereIn('status', ['present', 'late'])
                ->count();
            $expectedLogs = $workdays * $employeeCount;
            $attendanceHealth = $expectedLogs > 0 ? ($logsCount / $expectedLogs) * 100 : 0.0;
        } else {
            $attendanceHealth = 100.0;
        }

        // 3. Load configurable weights
        $weights = config('services.predictive.weights', [
            'attendance' => 0.25,
            'productivity' => 0.25,
            'consistency' => 0.25,
            'delivery' => 0.25
        ]);

        $productivityHealth = $metrics['productivity_score'] ?? 0.0;
        $consistencyHealth = $metrics['consistency_score'] ?? 0.0;
        $deliveryHealth = $metrics['deadline_adherence_rate'] ?? 0.0;

        // 4. Compute Health Score
        $score = ($attendanceHealth * $weights['attendance']) +
                 ($productivityHealth * $weights['productivity']) +
                 ($consistencyHealth * $weights['consistency']) +
                 ($deliveryHealth * $weights['delivery']);

        $score = max(0.0, min(100.0, $score));

        // 5. Determine status
        if ($score >= 85) {
            $status = 'Excellent';
        } elseif ($score >= 65) {
            $status = 'Healthy';
        } else {
            $status = 'Needs Attention';
        }

        return [
            'team_health_score' => round($score, 2),
            'status' => $status,
            'metrics' => [
                'attendance_health' => round($attendanceHealth, 2),
                'productivity_health' => round($productivityHealth, 2),
                'consistency_health' => round($consistencyHealth, 2),
                'delivery_health' => round($deliveryHealth, 2),
            ]
        ];
    }
}
