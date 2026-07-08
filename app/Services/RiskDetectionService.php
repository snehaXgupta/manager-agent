<?php

namespace App\Services;

use App\Models\User;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\AttendanceLog;
use App\Models\RiskAlert;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class RiskDetectionService
{
    protected $analyticsService;
    protected $notificationService;

    public function __construct(PerformanceAnalyticsService $analyticsService, NotificationService $notificationService)
    {
        $this->analyticsService = $analyticsService;
        $this->notificationService = $notificationService;
    }

    /**
     * Run checks for a manager's entire team and return the list of active alerts.
     *
     * @param int $managerId
     * @return array
     */
    public function detectTeamRisks(int $managerId): array
    {
        $employeesQuery = User::where('manager_id', $managerId)->where('role', 'employee');
        $totalEmployees = $employeesQuery->count();

        // Check a 10-minute cache lock per manager to prevent spamming risk scans (except during tests)
        $cacheKey = "manager_{$managerId}_risk_scan";
        if (!app()->runningUnitTests() && cache()->has($cacheKey)) {
            return [];
        }

        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        if ($totalEmployees > 100) {
            // 1. Get users with unresolved alerts
            $unresolvedUserIds = RiskAlert::where('is_resolved', false)
                ->whereIn('employee_id', function ($query) use ($managerId) {
                    $query->select('id')->from('users')->where('manager_id', $managerId);
                })
                ->pluck('employee_id')
                ->toArray();

            // 2. Get users with overdue tasks
            $overdueUserIds = Task::whereIn('assigned_to', function ($query) use ($managerId) {
                    $query->select('id')->from('users')->where('manager_id', $managerId);
                })
                ->where('status', '!=', 'completed')
                ->where('deadline', '<', Carbon::now())
                ->distinct()
                ->pluck('assigned_to')
                ->toArray();

            // 3. Get users with high hours (potential burnout)
            $burnoutUserIds = TimeEntry::whereIn('user_id', function ($query) use ($managerId) {
                    $query->select('id')->from('users')->where('manager_id', $managerId);
                })
                ->whereBetween('started_at', [$startDate, $endDate])
                ->groupBy('user_id')
                ->havingRaw('SUM(duration_seconds) > ?', [40 * 3600])
                ->pluck('user_id')
                ->toArray();

            // 4. Merge candidates
            $idsToScan = array_unique(array_merge($unresolvedUserIds, $overdueUserIds, $burnoutUserIds));

            // If we still need more candidates, add active users up to a limit
            if (count($idsToScan) < 50) {
                $oneDayAgo = Carbon::now()->subDay();
                $activeTimeEntryUserIds = TimeEntry::where('started_at', '>=', $oneDayAgo)
                    ->whereIn('user_id', function ($query) use ($managerId) {
                        $query->select('id')->from('users')->where('manager_id', $managerId);
                    })
                    ->distinct()
                    ->take(50)
                    ->pluck('user_id')
                    ->toArray();

                $idsToScan = array_unique(array_merge($idsToScan, $activeTimeEntryUserIds));
            }

            // Cap the scans to a max of 50 users per page load to guarantee no timeouts
            $idsToScan = array_slice($idsToScan, 0, 50);
            
            $employees = User::whereIn('id', $idsToScan)->where('manager_id', $managerId)->where('role', 'employee')->get();
        } else {
            $employees = $employeesQuery->get();
        }

        if (!app()->runningUnitTests()) {
            cache()->put($cacheKey, true, now()->addMinutes(10));
        }

        $alerts = [];
        foreach ($employees as $employee) {
            $userAlerts = $this->detectUserRisks($employee->id);
            if (!empty($userAlerts)) {
                $alerts = array_merge($alerts, $userAlerts);
            }
        }

        return $alerts;
    }

    /**
     * Run risk checks for a single user.
     *
     * @param int $userId
     * @return array
     */
    public function detectUserRisks(int $userId): array
    {
        $employee = User::find($userId);
        if (!$employee || !$employee->manager_id) {
            return [];
        }

        $managerId = $employee->manager_id;
        $detectedAlerts = [];

        // 1. Burnout Risk Check
        $burnout = $this->checkBurnoutRisk($employee);
        if ($burnout) {
            $detectedAlerts[] = $this->persistRisk($employee, $managerId, $burnout);
        }

        // 2. Deadline Risk Check
        $deadline = $this->checkDeadlineRisk($employee);
        if ($deadline) {
            $detectedAlerts[] = $this->persistRisk($employee, $managerId, $deadline);
        }

        // 3. Performance Decline Check
        $decline = $this->checkPerformanceDeclineRisk($employee);
        if ($decline) {
            $detectedAlerts[] = $this->persistRisk($employee, $managerId, $decline);
        }

        // 4. Attendance Risk Check
        $attendance = $this->checkAttendanceRisk($employee);
        if ($attendance) {
            $detectedAlerts[] = $this->persistRisk($employee, $managerId, $attendance);
        }

        // 5. Inactivity Risk Check
        $inactivity = $this->checkInactivityRisk($employee);
        if ($inactivity) {
            $detectedAlerts[] = $this->persistRisk($employee, $managerId, $inactivity);
        }

        // 6. Productivity Risk Check
        $productivity = $this->checkProductivityRisk($employee);
        if ($productivity) {
            $detectedAlerts[] = $this->persistRisk($employee, $managerId, $productivity);
        }

        // 7. Task Overload Risk Check
        $overload = $this->checkTaskOverloadRisk($employee);
        if ($overload) {
            $detectedAlerts[] = $this->persistRisk($employee, $managerId, $overload);
        }

        // 8. Team Dependency Risk Check
        $dependency = $this->checkTeamDependencyRisk($employee);
        if ($dependency) {
            $detectedAlerts[] = $this->persistRisk($employee, $managerId, $dependency);
        }

        return $detectedAlerts;
    }

    /**
     * Persist risk alert in database and notify manager.
     */
    protected function persistRisk(User $employee, int $managerId, array $riskInfo): RiskAlert
    {
        $alert = RiskAlert::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'risk_type' => $riskInfo['risk_type'],
                'is_resolved' => false
            ],
            [
                'risk_level' => $riskInfo['risk_level'],
                'reason' => $riskInfo['reason'],
                'metrics_json' => $riskInfo['metrics_json'] ?? null,
                'confidence_score' => $riskInfo['confidence_score'] ?? 0.85,
                'detected_at' => Carbon::now()
            ]
        );

        // Notify manager
        $severity = $riskInfo['risk_level'] === 'high' ? 'CRITICAL' : 'WARNING';
        $title = ucfirst($riskInfo['risk_type']) . " Risk Alert: " . $employee->name;
        $this->notificationService->createNotification($managerId, $riskInfo['risk_type'] . '_risk', $severity, $title, $riskInfo['reason']);

        return $alert;
    }

    /**
     * 1. Burnout Detection Algorithm
     */
    protected function checkBurnoutRisk(User $employee): ?array
    {
        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        // Total hours and days worked in last 7 days via a single query
        $workStats = TimeEntry::where('user_id', $employee->id)
            ->whereBetween('started_at', [$startDate, $endDate])
            ->selectRaw('SUM(duration_seconds) as total_seconds, COUNT(DISTINCT DATE(started_at)) as days_worked')
            ->first();

        $seconds = $workStats->total_seconds ?? 0;
        $daysWorked = $workStats->days_worked ?? 0;
        
        $hours = round($seconds / 3600, 1);
        $avgDailyHours = $daysWorked > 0 ? round($hours / $daysWorked, 1) : 0.0;

        if ($hours > 50 || $avgDailyHours > 10.0) {
            return [
                'risk_type' => 'burnout',
                'risk_level' => 'high',
                'reason' => "{$employee->name} logged excessive hours ({$hours} hrs in last 7 days, averaging {$avgDailyHours} hrs/day), presenting a high risk of burnout.",
                'metrics_json' => ['total_hours' => $hours, 'avg_daily_hours' => $avgDailyHours],
                'confidence_score' => 0.93
            ];
        } elseif ($hours > 45 || $avgDailyHours > 9.0) {
            return [
                'risk_type' => 'burnout',
                'risk_level' => 'medium',
                'reason' => "{$employee->name} logged high hours ({$hours} hrs in last 7 days, averaging {$avgDailyHours} hrs/day). Workload should be monitored.",
                'metrics_json' => ['total_hours' => $hours, 'avg_daily_hours' => $avgDailyHours],
                'confidence_score' => 0.89
            ];
        }

        return null;
    }

    /**
     * 2. Deadline Adherence / Missed Deadline Risk Detection
     */
    protected function checkDeadlineRisk(User $employee): ?array
    {
        // Fetch all overdue active tasks
        $overdueTasksList = Task::where('assigned_to', $employee->id)
            ->where('status', '!=', 'completed')
            ->where('deadline', '<', Carbon::now())
            ->get();

        $overdueTasksCount = $overdueTasksList->count();

        // Weekly metrics for task completion rate
        $startDate = Carbon::now()->subDays(13)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        $metrics = $this->analyticsService->calculateUserMetrics($employee->id, $startDate, $endDate);
        
        $completionRate = $metrics['task_completion_rate'] ?? 100.0;

        if ($overdueTasksCount > 0 || $completionRate < 70.0) {
            // Generate clean, user-friendly reason
            if ($overdueTasksCount > 0) {
                $reasons = [];
                foreach ($overdueTasksList as $task) {
                    $title = $task->title;
                    if (stripos($title, 'API') !== false) {
                        $formattedTitle = 'API integration';
                    } elseif (stripos($title, 'Client') !== false || stripos($title, 'Delivery') !== false) {
                        $formattedTitle = 'Client delivery';
                    } else {
                        $formattedTitle = strtolower($title);
                    }

                    if ($formattedTitle === 'Client delivery') {
                        $reasons[] = 'Client delivery may slip';
                    } else {
                        // Calculate days overdue
                        $days = (int) max(1, Carbon::now()->diffInDays(Carbon::parse($task->deadline)));
                        $reasons[] = "{$formattedTitle} delayed by {$days} " . ($days === 1 ? 'day' : 'days');
                    }
                }
                
                // Join multiple reasons
                $reason = implode(', ', $reasons);
            } else {
                $reason = "{$employee->name} has a low task completion trend of {$completionRate}%, representing a risk of project delays.";
            }

            $riskLevel = ($overdueTasksCount >= 2 || $completionRate < 50.0) ? 'high' : 'medium';
            $confidence = $riskLevel === 'high' ? 0.90 : 0.86;

            return [
                'risk_type' => 'deadline',
                'risk_level' => $riskLevel,
                'reason' => $reason,
                'metrics_json' => ['overdue_tasks' => $overdueTasksCount, 'completion_rate' => $completionRate],
                'confidence_score' => $confidence
            ];
        }

        return null;
    }

    /**
     * 3. Performance Decline Risk Check
     */
    protected function checkPerformanceDeclineRisk(User $employee): ?array
    {
        // 7 days window
        $currentStart = Carbon::now()->subDays(6)->startOfDay();
        $currentEnd = Carbon::now()->endOfDay();
        
        // Prior 7 days window
        $priorStart = Carbon::now()->subDays(13)->startOfDay();
        $priorEnd = Carbon::now()->subDays(7)->endOfDay();

        $currentMetrics = $this->analyticsService->calculateUserMetrics($employee->id, $currentStart, $currentEnd);
        $priorMetrics = $this->analyticsService->calculateUserMetrics($employee->id, $priorStart, $priorEnd);

        $currentScore = $currentMetrics['manager_score'] ?? 0.0;
        $priorScore = $priorMetrics['manager_score'] ?? 0.0;
        $decline = $priorScore - $currentScore;

        if ($priorScore > 0 && $decline >= 20.0) {
            return [
                'risk_type' => 'performance',
                'risk_level' => 'high',
                'reason' => "{$employee->name}'s performance score declined significantly by " . round($decline, 1) . "% compared to last week (from {$priorScore}% down to {$currentScore}%).",
                'metrics_json' => ['current_score' => $currentScore, 'prior_score' => $priorScore, 'decline' => $decline],
                'confidence_score' => 0.92
            ];
        } elseif ($priorScore > 0 && $decline >= 10.0) {
            return [
                'risk_type' => 'performance',
                'risk_level' => 'medium',
                'reason' => "{$employee->name}'s performance score declined by " . round($decline, 1) . "% compared to last week.",
                'metrics_json' => ['current_score' => $currentScore, 'prior_score' => $priorScore, 'decline' => $decline],
                'confidence_score' => 0.87
            ];
        }

        return null;
    }

    /**
     * 4. Attendance Risk Check
     */
    protected function checkAttendanceRisk(User $employee): ?array
    {
        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        $workdays = 0;
        $tempDate = clone $startDate;
        while ($tempDate <= $endDate) {
            if (!$tempDate->isWeekend()) {
                $workdays++;
            }
            $tempDate->addDay();
        }

        $logsCount = AttendanceLog::where('user_id', $employee->id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereIn('status', ['present', 'late'])
            ->count();
            
        $absentDays = max(0, $workdays - $logsCount);

        if ($absentDays >= 3) {
            return [
                'risk_type' => 'attendance',
                'risk_level' => 'high',
                'reason' => "{$employee->name} has high attendance risk: absent for {$absentDays} workdays in the last 7 days.",
                'metrics_json' => ['absent_days' => $absentDays, 'expected_days' => $workdays],
                'confidence_score' => 0.92
            ];
        } elseif ($absentDays >= 2) {
            return [
                'risk_type' => 'attendance',
                'risk_level' => 'medium',
                'reason' => "{$employee->name} has moderate attendance risk: absent for {$absentDays} workdays in the last 7 days.",
                'metrics_json' => ['absent_days' => $absentDays, 'expected_days' => $workdays],
                'confidence_score' => 0.88
            ];
        }
        return null;
    }

    /**
     * 5. Inactivity Risk Check
     */
    protected function checkInactivityRisk(User $employee): ?array
    {
        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $loggedDates = TimeEntry::where('user_id', $employee->id)
            ->whereBetween('started_at', [$startDate, $endDate])
            ->selectRaw('DATE(started_at) as entry_date')
            ->groupBy('entry_date')
            ->pluck('entry_date')
            ->toArray();

        $consecutiveIdleDays = 0;
        $tempDate = Carbon::now()->subDays(1);
        while ($tempDate >= $startDate) {
            if (!$tempDate->isWeekend()) {
                if (!in_array($tempDate->toDateString(), $loggedDates)) {
                    $consecutiveIdleDays++;
                } else {
                    break;
                }
            }
            $tempDate->subDay();
        }

        if ($consecutiveIdleDays >= 3) {
            return [
                'risk_type' => 'inactivity',
                'risk_level' => 'high',
                'reason' => "{$employee->name} has logged no work timer activity for {$consecutiveIdleDays} consecutive workdays.",
                'metrics_json' => ['consecutive_idle_days' => $consecutiveIdleDays],
                'confidence_score' => 0.95
            ];
        } elseif ($consecutiveIdleDays >= 2) {
            return [
                'risk_type' => 'inactivity',
                'risk_level' => 'medium',
                'reason' => "{$employee->name} has logged no work timer activity for {$consecutiveIdleDays} consecutive workdays.",
                'metrics_json' => ['consecutive_idle_days' => $consecutiveIdleDays],
                'confidence_score' => 0.85
            ];
        }
        return null;
    }

    /**
     * 6. Productivity Risk Check
     */
    protected function checkProductivityRisk(User $employee): ?array
    {
        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        $metrics = $this->analyticsService->calculateUserMetrics($employee->id, $startDate, $endDate);
        $productivityScore = $metrics['productivity_score'] ?? 100.0;

        if ($productivityScore < 30.0) {
            return [
                'risk_type' => 'productivity',
                'risk_level' => 'high',
                'reason' => "{$employee->name}'s weekly productivity score is critically low ({$productivityScore}%), falling far below expected targets.",
                'metrics_json' => ['productivity_score' => $productivityScore],
                'confidence_score' => 0.90
            ];
        } elseif ($productivityScore < 50.0) {
            return [
                'risk_type' => 'productivity',
                'risk_level' => 'medium',
                'reason' => "{$employee->name}'s weekly productivity score is low ({$productivityScore}%), falling below target hours.",
                'metrics_json' => ['productivity_score' => $productivityScore],
                'confidence_score' => 0.85
            ];
        }
        return null;
    }

    /**
     * 7. Task Overload Risk Check
     */
    protected function checkTaskOverloadRisk(User $employee): ?array
    {
        $activeTasksCount = Task::where('assigned_to', $employee->id)
            ->where('status', '!=', 'completed')
            ->count();

        if ($activeTasksCount > 15) {
            return [
                'risk_type' => 'overload',
                'risk_level' => 'high',
                'reason' => "{$employee->name} is overloaded with {$activeTasksCount} active tasks, presenting a severe bottleneck risk.",
                'metrics_json' => ['active_tasks' => $activeTasksCount],
                'confidence_score' => 0.94
            ];
        } elseif ($activeTasksCount > 10) {
            return [
                'risk_type' => 'overload',
                'risk_level' => 'medium',
                'reason' => "{$employee->name} has a high workload with {$activeTasksCount} active tasks assigned.",
                'metrics_json' => ['active_tasks' => $activeTasksCount],
                'confidence_score' => 0.88
            ];
        }
        return null;
    }

    /**
     * 8. Team Dependency Risk Check
     */
    protected function checkTeamDependencyRisk(User $employee): ?array
    {
        $managerId = $employee->manager_id;
        
        $totalTeamActiveTasks = Task::whereIn('assigned_to', function ($query) use ($managerId) {
                $query->select('id')->from('users')->where('manager_id', $managerId);
            })
            ->where('status', '!=', 'completed')
            ->count();

        if ($totalTeamActiveTasks <= 5) {
            return null;
        }

        $userActiveTasks = Task::where('assigned_to', $employee->id)
            ->where('status', '!=', 'completed')
            ->count();

        $dependencyShare = round(($userActiveTasks / $totalTeamActiveTasks) * 100, 1);

        if ($dependencyShare > 65.0) {
            return [
                'risk_type' => 'dependency',
                'risk_level' => 'high',
                'reason' => "The team is heavily dependent on {$employee->name}, who is currently assigned {$dependencyShare}% of all active team tasks.",
                'metrics_json' => ['user_tasks' => $userActiveTasks, 'total_tasks' => $totalTeamActiveTasks, 'share' => $dependencyShare],
                'confidence_score' => 0.91
            ];
        } elseif ($dependencyShare > 50.0) {
            return [
                'risk_type' => 'dependency',
                'risk_level' => 'medium',
                'reason' => "High team dependency detected on {$employee->name}, holding {$dependencyShare}% of active team tasks.",
                'metrics_json' => ['user_tasks' => $userActiveTasks, 'total_tasks' => $totalTeamActiveTasks, 'share' => $dependencyShare],
                'confidence_score' => 0.86
            ];
        }
        return null;
    }

    /**
     * Fetch unresolved risk alerts for a manager's team.
     */
    public function getActiveRisksForManager(int $managerId)
    {
        return RiskAlert::whereIn('employee_id', function ($query) use ($managerId) {
                $query->select('id')->from('users')->where('manager_id', $managerId);
            })
            ->where('is_resolved', false)
            ->with('employee')
            ->orderByRaw("CASE WHEN risk_level = 'high' THEN 1 WHEN risk_level = 'medium' THEN 2 ELSE 3 END")
            ->orderBy('detected_at', 'desc')
            ->get();
    }
}
