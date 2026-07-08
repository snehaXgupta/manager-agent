<?php

namespace App\Services;

use App\Models\PerformanceReport;
use App\Models\User;
use App\Models\LeaveRequest;
use App\Models\AttendanceLog;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\DeveloperActivity;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class LeaderboardService
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Get the leaderboard for a given period ('weekly' or 'monthly').
     *
     * @param string $period 'weekly'|'monthly'
     * @return array
     */
    public function getLeaderboard(string $period = 'weekly'): array
    {
        $period = in_array($period, ['weekly', 'monthly']) ? $period : 'weekly';

        $cacheKey = "leaderboard_{$period}";
        
        return Cache::remember($cacheKey, 3600, function () use ($period) {
            return $this->buildLeaderboard($period);
        });
    }

    /**
     * Clear cached leaderboard data.
     *
     * @param string $period 'weekly'|'monthly'
     * @return void
     */
    public function clearCache(string $period): void
    {
        Cache::forget("leaderboard_weekly");
        Cache::forget("leaderboard_monthly");
    }

    /**
     * Build rankings and compare with the previous period.
     */
    protected function buildLeaderboard(string $period): array
    {
        // 1. Determine date ranges for current and previous period
        $now = Carbon::now();
        if ($period === 'monthly') {
            $currentStart = $now->copy()->subDays(29)->startOfDay();
            $currentEnd = $now->copy()->endOfDay();
            
            $previousStart = $now->copy()->subDays(59)->startOfDay();
            $previousEnd = $now->copy()->subDays(30)->endOfDay();
        } else { // weekly
            $currentStart = $now->copy()->subDays(6)->startOfDay();
            $currentEnd = $now->copy()->endOfDay();
            
            $previousStart = $now->copy()->subDays(13)->startOfDay();
            $previousEnd = $now->copy()->subDays(7)->endOfDay();
        }

        $managers = User::where('role', 'manager')->get();
        $managerIds = $managers->pluck('id');

        // Fetch recent reports in bulk
        $recentReports = PerformanceReport::whereIn('manager_id', $managerIds)
            ->where('report_type', $period)
            ->orderBy('generated_at', 'desc')
            ->get()
            ->groupBy('manager_id');

        // Fetch previous reports in bulk
        $previousReports = PerformanceReport::whereIn('manager_id', $managerIds)
            ->where('report_type', $period)
            ->whereBetween('period_start', [$previousStart->toDateString(), $previousEnd->toDateString()])
            ->orderBy('generated_at', 'desc')
            ->get()
            ->groupBy('manager_id');

        // 2. Fetch/Calculate current reports and rank managers
        $currentRankings = [];
        foreach ($managers as $manager) {
            $report = $recentReports->has($manager->id) ? $recentReports->get($manager->id)->first() : null;

            if (!$report) {
                try {
                    // Calculate score dynamically without Ollama AI and without persisting a new report
                    $analyticsService = app(PerformanceAnalyticsService::class);
                    $metrics = $analyticsService->calculateTeamMetrics($manager->id, $currentStart, $currentEnd);
                    $score = $metrics['manager_score'];
                } catch (\Exception $e) {
                    $score = 0.0;
                }
                $reportId = null;
            } else {
                $score = (float) $report->manager_score;
                $reportId = $report->id;
            }

            $currentRankings[] = [
                'manager_id' => $manager->id,
                'manager_name' => $manager->name,
                'manager_score' => $score,
                'report_id' => $reportId,
            ];
        }

        // Sort current rankings descending by score
        usort($currentRankings, function ($a, $b) {
            return $b['manager_score'] <=> $a['manager_score'];
        });

        // Add Rank Number to Current Rankings
        foreach ($currentRankings as $index => &$ranking) {
            $ranking['rank'] = $index + 1;
        }
        unset($ranking); // break reference

        // 3. Fetch/Generate previous reports to compute previous ranks
        $previousRankings = [];
        foreach ($managers as $manager) {
            $report = $previousReports->has($manager->id) ? $previousReports->get($manager->id)->first() : null;

            // If no historic report exists for the exact previous timeframe, dynamically build it but don't persist
            // unless necessary, or generate a quick one. Let's build it dynamically.
            if (!$report) {
                try {
                    // Temporarily run analytics for previous period
                    $analyticsService = app(PerformanceAnalyticsService::class);
                    $metrics = $analyticsService->calculateTeamMetrics($manager->id, $previousStart, $previousEnd);
                    $prevScore = $metrics['manager_score'];
                } catch (\Exception $e) {
                    $prevScore = 0.0;
                }
            } else {
                $prevScore = (float) $report->manager_score;
            }

            $previousRankings[] = [
                'manager_id' => $manager->id,
                'manager_score' => $prevScore,
            ];
        }

        // Sort previous rankings to assign ranks
        usort($previousRankings, function ($a, $b) {
            return $b['manager_score'] <=> $a['manager_score'];
        });

        $prevRankMap = [];
        foreach ($previousRankings as $index => $ranking) {
            $prevRankMap[$ranking['manager_id']] = [
                'rank' => $index + 1,
                'score' => $ranking['manager_score']
            ];
        }

        // 4. Merge current and previous ranking comparison
        $leaderboard = [];
        foreach ($currentRankings as $curr) {
            $managerId = $curr['manager_id'];
            $prev = $prevRankMap[$managerId] ?? null;

            if ($prev && $prev['score'] > 0) {
                $rankChange = $prev['rank'] - $curr['rank']; // positive is improvement
                $scoreTrend = round($curr['manager_score'] - $prev['score'], 2);
                $prevRank = $prev['rank'];
                $prevScore = $prev['score'];
            } else {
                $rankChange = 0; // new or no previous data
                $scoreTrend = 0.0;
                $prevRank = null;
                $prevScore = null;
            }

            $leaderboard[] = [
                'rank' => $curr['rank'],
                'manager_name' => $curr['manager_name'],
                'manager_score' => $curr['manager_score'],
                'rank_change' => $rankChange,
                'score_trend' => $scoreTrend,
                'previous_rank' => $prevRank,
                'previous_score' => $prevScore,
                'report_id' => $curr['report_id'],
            ];
        }

        return [
            'period' => $period,
            'date_range' => [
                'start' => $currentStart->toDateString(),
                'end' => $currentEnd->toDateString()
            ],
            'rankings' => $leaderboard
        ];
    }

    /**
     * Resolve period name to start and end Carbon dates.
     */
    public function getPeriodDates(string $period, ?string $customStart = null, ?string $customEnd = null): array
    {
        $now = Carbon::now();
        $startDate = null;
        $endDate = $now->copy()->endOfDay();

        switch ($period) {
            case 'daily':
                $startDate = $now->copy()->startOfDay();
                break;
            case 'weekly':
                $startDate = $now->copy()->subDays(6)->startOfDay();
                break;
            case 'monthly':
                $startDate = $now->copy()->subDays(29)->startOfDay();
                break;
            case 'quarterly':
                $startDate = $now->copy()->subDays(89)->startOfDay();
                break;
            case 'yearly':
                $startDate = $now->copy()->subDays(364)->startOfDay();
                break;
            case 'custom':
                if ($customStart && $customEnd) {
                    $startDate = Carbon::parse($customStart)->startOfDay();
                    $endDate = Carbon::parse($customEnd)->endOfDay();
                } else {
                    $startDate = $now->copy()->subDays(6)->startOfDay(); // fallback to weekly
                }
                break;
            default:
                $startDate = $now->copy()->subDays(6)->startOfDay(); // weekly is default
                break;
        }

        return [
            'start' => $startDate,
            'end' => $endDate,
        ];
    }

    /**
     * Helper to compute attendance score and percentage for a user.
     */
    protected function calculateAttendanceScoreForUser(User $employee, Carbon $startDate, Carbon $endDate): array
    {
        // Calculate actual workdays (weekdays) in period
        $workdays = 0;
        $tempDate = clone $startDate;
        while ($tempDate <= $endDate) {
            if (!$tempDate->isWeekend()) {
                $workdays++;
            }
            $tempDate->addDay();
        }

        // Fetch approved leaves within the period and calculate their weekdays
        $approvedLeaves = LeaveRequest::where('user_id', $employee->id)
            ->where('status', 'approved')
            ->where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function($sub) use ($startDate, $endDate) {
                      $sub->where('start_date', '<=', $startDate)
                          ->where('end_date', '>=', $endDate);
                  });
            })
            ->get();

        $leaveWeekdays = 0;
        foreach ($approvedLeaves as $leave) {
            $leaveStart = Carbon::parse($leave->start_date);
            $leaveEnd = Carbon::parse($leave->end_date);
            $lStart = $leaveStart->gt($startDate) ? $leaveStart : $startDate;
            $lEnd = $leaveEnd->lt($endDate) ? $leaveEnd : $endDate;
            $lTemp = clone $lStart;
            while ($lTemp <= $lEnd) {
                if (!$lTemp->isWeekend()) {
                    $leaveWeekdays++;
                }
                $lTemp->addDay();
            }
        }

        // Expected attendance days is workdays minus approved leave weekdays
        $expectedDays = max(0, $workdays - $leaveWeekdays);

        // Fetch attendance logs
        $logs = AttendanceLog::where('user_id', $employee->id)
            ->whereBetween('date', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->get();

        $presentCount = $logs->whereIn('status', ['present', 'late'])->count();
        $lateCount = $logs->where('status', 'late')->count();
        $earlyExits = $logs->where('is_early_exit', true)->count();

        // Absent days count
        $absentCount = max(0, $expectedDays - $presentCount);

        // Attendance Percentage
        $attendancePercentage = $expectedDays > 0 ? ($presentCount / $expectedDays) * 100 : 100.0;

        // Attendance Score Formula: 100 - (late * 5) - (absent * 15) - (early_exits * 5)
        $score = 100 - ($lateCount * 5) - ($absentCount * 15) - ($earlyExits * 5);
        $score = max(0, min(100, $score));

        return [
            'attendance_percentage' => round($attendancePercentage, 2),
            'attendance_score' => $score,
            'present_days' => $presentCount,
            'late_days' => $lateCount,
            'absent_days' => $absentCount,
            'early_exits' => $earlyExits,
        ];
    }

    /**
     * Compute performance metrics in bulk for a collection of employees.
     */
    protected function computeBulkLeaderboard(Collection $employees, Carbon $startDate, Carbon $endDate): array
    {
        if ($employees->isEmpty()) {
            return [];
        }

        $employeeIds = $employees->pluck('id')->toArray();
        $analyticsService = app(PerformanceAnalyticsService::class);

        // 1. Bulk fetch task stats
        $tasksData = Task::whereIn('assigned_to', $employeeIds)
            ->where('created_at', '<=', $endDate)
            ->selectRaw("assigned_to, 
                COUNT(*) as total_count,
                SUM(CASE WHEN status = 'completed' AND updated_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN status = 'completed' AND updated_at BETWEEN ? AND ? AND (deadline IS NULL OR updated_at <= deadline) THEN 1 ELSE 0 END) as completed_on_time_count", 
                [$startDate->toDateTimeString(), $endDate->toDateTimeString(), $startDate->toDateTimeString(), $endDate->toDateTimeString()]
            )
            ->groupBy('assigned_to')
            ->get()
            ->keyBy('assigned_to');

        // 2. Bulk fetch developer activities
        $activitiesData = DeveloperActivity::whereIn('user_id', $employeeIds)
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->selectRaw('user_id, event_type, COUNT(*) as count')
            ->groupBy('user_id', 'event_type')
            ->get()
            ->groupBy('user_id');

        // Map activities to plain nested array to avoid collection overhead in the loop
        $gitActivitiesMap = [];
        foreach ($activitiesData as $userId => $activities) {
            foreach ($activities as $act) {
                $gitActivitiesMap[$userId][$act->event_type] = $act->count;
            }
        }
        unset($activitiesData);

        // 3. Bulk fetch time entries sum
        $timeEntriesSum = TimeEntry::whereIn('user_id', $employeeIds)
            ->whereBetween('started_at', [$startDate, $endDate])
            ->selectRaw('user_id, SUM(duration_seconds) as total_seconds')
            ->groupBy('user_id')
            ->pluck('total_seconds', 'user_id');

        // 4. Bulk fetch daily time entries (for consistency)
        $dailySecondsData = TimeEntry::whereIn('user_id', $employeeIds)
            ->whereBetween('started_at', [$startDate, $endDate])
            ->selectRaw('user_id, DATE(started_at) as entry_date, SUM(duration_seconds) as total_seconds')
            ->groupBy('user_id', 'entry_date')
            ->get();

        // Map daily seconds to plain nested array
        $dailySecondsMapAll = [];
        foreach ($dailySecondsData as $entry) {
            $dailySecondsMapAll[$entry->user_id][$entry->entry_date] = $entry->total_seconds;
        }
        unset($dailySecondsData);

        // 5. Pre-calculate leaves weekdays map per employee to avoid loops and date operations inside employee loop
        $leaveWeekdaysMap = [];
        $rawLeaves = LeaveRequest::whereIn('user_id', $employeeIds)
            ->where('status', 'approved')
            ->where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function($sub) use ($startDate, $endDate) {
                      $sub->where('start_date', '<=', $startDate)
                          ->where('end_date', '>=', $endDate);
                  });
            })
            ->get();

        foreach ($rawLeaves as $leave) {
            $leaveStart = Carbon::parse($leave->start_date);
            $leaveEnd = Carbon::parse($leave->end_date);
            $lStart = $leaveStart->gt($startDate) ? $leaveStart : $startDate;
            $lEnd = $leaveEnd->lt($endDate) ? $leaveEnd : $endDate;
            $lTemp = clone $lStart;
            $cnt = 0;
            while ($lTemp <= $lEnd) {
                if (!$lTemp->isWeekend()) {
                    $cnt++;
                }
                $lTemp->addDay();
            }
            $leaveWeekdaysMap[$leave->user_id] = ($leaveWeekdaysMap[$leave->user_id] ?? 0) + $cnt;
        }

        // 6. Pre-calculate year leaves weekdays map per employee
        $yearStart = Carbon::createFromDate($startDate->year, 1, 1)->startOfYear();
        $yearEnd = Carbon::createFromDate($startDate->year, 12, 31)->endOfYear();
        $yearLeaveWeekdaysMap = [];
        $rawYearLeaves = LeaveRequest::whereIn('user_id', $employeeIds)
            ->where('status', 'approved')
            ->where(function($q) use ($yearStart, $yearEnd) {
                $q->whereBetween('start_date', [$yearStart, $yearEnd])
                  ->orWhereBetween('end_date', [$yearStart, $yearEnd])
                  ->orWhere(function($sub) use ($yearStart, $yearEnd) {
                      $sub->where('start_date', '<=', $yearStart)
                          ->where('end_date', '>=', $yearEnd);
                  });
            })
            ->get();

        foreach ($rawYearLeaves as $leave) {
            $leaveStart = Carbon::parse($leave->start_date);
            $leaveEnd = Carbon::parse($leave->end_date);
            $lStart = $leaveStart->gt($yearStart) ? $leaveStart : $yearStart;
            $lEnd = $leaveEnd->lt($yearEnd) ? $yearEnd : $yearEnd;
            $lTemp = clone $lStart;
            $cnt = 0;
            while ($lTemp <= $lEnd) {
                if (!$lTemp->isWeekend()) {
                    $cnt++;
                }
                $lTemp->addDay();
            }
            $yearLeaveWeekdaysMap[$leave->user_id] = ($yearLeaveWeekdaysMap[$leave->user_id] ?? 0) + $cnt;
        }

        // 7. Bulk fetch attendance logs
        $logs = AttendanceLog::whereIn('user_id', $employeeIds)
            ->whereBetween('date', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->get()
            ->groupBy('user_id');

        // Pre-calculate attendance stats to avoid collection overhead in the loop
        $attendanceStatsMap = [];
        foreach ($logs as $userId => $userLogs) {
            $presentCount = 0;
            $lateCount = 0;
            $earlyExits = 0;
            foreach ($userLogs as $log) {
                if ($log->status === 'present' || $log->status === 'late') {
                    $presentCount++;
                }
                if ($log->status === 'late') {
                    $lateCount++;
                }
                if ($log->is_early_exit) {
                    $earlyExits++;
                }
            }
            $attendanceStatsMap[$userId] = [
                'present' => $presentCount,
                'late' => $lateCount,
                'early_exits' => $earlyExits,
            ];
        }
        unset($logs);

        // Pre-calculate workdays and weekdays date strings in the period
        $workdays = 0;
        $weekdays = [];
        $tempDate = clone $startDate;
        while ($tempDate <= $endDate) {
            if (!$tempDate->isWeekend()) {
                $workdays++;
                $weekdays[] = $tempDate->toDateString();
            }
            $tempDate->addDay();
        }
        $expectedHours = $workdays * 8;

        $rankings = [];
        foreach ($employees as $employee) {
            $employeeId = $employee->id;

            // 1. Task Metrics
            $stats = $tasksData->get($employeeId);
            $totalTasks = $stats ? (int)$stats->total_count : 0;
            $completedTasksCount = $stats ? (int)$stats->completed_count : 0;
            $completedOnTimeCount = $stats ? (int)$stats->completed_on_time_count : 0;

            $taskCompletionRate = $totalTasks > 0 ? ($completedTasksCount / $totalTasks) * 100 : 100.0;
            $deadlineAdherenceRate = $completedTasksCount > 0 ? ($completedOnTimeCount / $completedTasksCount) * 100 : 100.0;

            // 2. Productivity Metrics
            $totalSecondsLogged = $timeEntriesSum->get($employeeId, 0);
            $totalHoursLogged = $totalSecondsLogged / 3600;
            $productivityScore = $expectedHours > 0 ? ($totalHoursLogged / $expectedHours) * 100 : 0.0;
            $productivityScore = min($productivityScore, 100.0);

            // 3. Consistency Metrics
            $userDailySeconds = $dailySecondsMapAll[$employeeId] ?? [];
            $dailyHours = [];
            foreach ($weekdays as $dateStr) {
                $secondsForDay = $userDailySeconds[$dateStr] ?? 0;
                $dailyHours[] = $secondsForDay / 3600;
            }
            $consistencyScore = $analyticsService->calculateConsistency($dailyHours);

            // 4. Git Activity Metrics
            $userGit = $gitActivitiesMap[$employeeId] ?? [];
            $commitsCount = $userGit['commit'] ?? 0;
            $reviewsCount = $userGit['review_submitted'] ?? 0;

            $codeQualityScore = $commitsCount > 0 ? min(80.0 + ($commitsCount * 2.0), 100.0) : 80.0;
            $reviewsScore = $reviewsCount > 0 ? min(70.0 + ($reviewsCount * 10.0), 100.0) : 70.0;
            $deliverySpeedScore = $deadlineAdherenceRate;

            // 5. Developer Score
            $developerScore = (0.40 * $taskCompletionRate) + 
                              (0.20 * $codeQualityScore) + 
                              (0.20 * $reviewsScore) + 
                              (0.20 * $deliverySpeedScore);

            // 6. Attendance Score
            $leaveWeekdays = $leaveWeekdaysMap[$employeeId] ?? 0;
            $expectedDays = max(0, $workdays - $leaveWeekdays);

            $attStats = $attendanceStatsMap[$employeeId] ?? ['present' => 0, 'late' => 0, 'early_exits' => 0];
            $presentCount = $attStats['present'];
            $lateCount = $attStats['late'];
            $earlyExits = $attStats['early_exits'];
            $absentCount = max(0, $expectedDays - $presentCount);

            $attendanceScore = 100 - ($lateCount * 5) - ($absentCount * 15) - ($earlyExits * 5);
            $attendanceScore = max(0, min(100, $attendanceScore));

            // 7. Overall Score
            $overallScore = (0.70 * $developerScore) + (0.30 * $attendanceScore);
            $overallScore = round($overallScore, 2);

            $rankings[] = [
                'employee_id' => $employeeId,
                'employee_name' => $employee->name,
                'department_name' => $employee->department ? $employee->department->name : 'N/A',
                'designation_name' => $employee->designation ? $employee->designation->name : 'N/A',
                'task_completion_rate' => round($taskCompletionRate, 2),
                'attendance_score' => round($attendanceScore, 2),
                'code_quality_score' => round($codeQualityScore, 2),
                'git_commits_count' => $commitsCount,
                'overall_score' => $overallScore,
                'productivity_score' => round($productivityScore, 2),
                'consistency_score' => round($consistencyScore, 2),
                'deadline_adherence_rate' => round($deadlineAdherenceRate, 2),
                'reviews_score' => round($reviewsScore, 2),
                'delivery_speed_score' => round($deliverySpeedScore, 2),
            ];
        }

        // Sort rankings descending by overall_score
        usort($rankings, function ($a, $b) {
            return $b['overall_score'] <=> $a['overall_score'];
        });

        // Add ranks
        foreach ($rankings as $index => &$rank) {
            $rank['rank'] = $index + 1;
        }
        unset($rank);

        return $rankings;
    }

    /**
     * Get individual rankings for a manager's team or system-wide.
     */
    public function getIndividualLeaderboard(int $managerId, Carbon $startDate, Carbon $endDate, array $filters = []): array
    {
        $cacheKey = "individual_leaderboard_{$managerId}_" . 
                    "{$startDate->toDateString()}_{$endDate->toDateString()}_" . 
                    ($filters['scope'] ?? 'team') . "_" . 
                    ($filters['department_id'] ?? '') . "_" . 
                    ($filters['team_id'] ?? '');

        return Cache::remember($cacheKey, 300, function () use ($managerId, $startDate, $endDate, $filters) {
            $query = User::where('role', 'employee');

            // Scope to manager's reports unless "all" or specific scope is allowed/requested
            if (($filters['scope'] ?? 'team') === 'team') {
                $query->where('manager_id', $managerId);
            }

            // Department filter
            if (!empty($filters['department_id'])) {
                $query->where('department_id', $filters['department_id']);
            }

            // Team filter
            if (!empty($filters['team_id'])) {
                $teamId = $filters['team_id'];
                $query->whereHas('teams', function ($q) use ($teamId) {
                    $q->where('teams.id', $teamId);
                });
            }

            $employees = $query->with(['department', 'designation'])->get();
            $rankings = $this->computeBulkLeaderboard($employees, $startDate, $endDate);
            return array_slice($rankings, 0, 100);
        });
    }

    /**
     * Get team leaderboards ranking manager's created teams.
     */
    public function getTeamLeaderboard(int $managerId, Carbon $startDate, Carbon $endDate, bool $systemWide = false): array
    {
        $cacheKey = "team_leaderboard_{$managerId}_{$startDate->toDateString()}_{$endDate->toDateString()}_" . ($systemWide ? '1' : '0');

        return Cache::remember($cacheKey, 300, function () use ($managerId, $startDate, $endDate, $systemWide) {
            $query = \App\Models\Team::with('members')->take(100);

            if (!$systemWide) {
                $query->where('manager_id', $managerId);
            }

            $teams = $query->get();

            // Fetch metrics in bulk for all team members at once (sample up to 50 members per team for calculation speed)
            $allMembers = $teams->flatMap(function($team) {
                return $team->members->take(50);
            })->unique('id');

            $memberRankings = [];
            if ($allMembers->isNotEmpty()) {
                $memberRankings = $this->computeBulkLeaderboard($allMembers, $startDate, $endDate);
            }
            $memberMetricsMap = collect($memberRankings)->keyBy('employee_id');

            $rankings = [];
            foreach ($teams as $team) {
                $members = $team->members;
                if ($members->isEmpty()) {
                    $rankings[] = [
                        'team_id' => $team->id,
                        'team_name' => $team->name,
                        'productivity' => 0.0,
                        'delivery' => 0.0,
                        'attendance' => 0.0,
                        'code_quality' => 0.0,
                        'collaboration' => 0.0,
                        'overall_score' => 0.0,
                        'members_count' => 0,
                    ];
                    continue;
                }

                $totalProductivity = 0;
                $totalDelivery = 0;
                $totalAttendance = 0;
                $totalCodeQuality = 0;
                $totalCollaboration = 0;

                $sampledMembers = $members->take(50);
                foreach ($sampledMembers as $member) {
                    $metrics = $memberMetricsMap->get($member->id);

                    $totalProductivity += $metrics ? $metrics['productivity_score'] : 0;
                    $totalDelivery += $metrics ? $metrics['deadline_adherence_rate'] : 100.0;
                    $totalAttendance += $metrics ? $metrics['attendance_score'] : 100.0;
                    $totalCodeQuality += $metrics ? $metrics['code_quality_score'] : 80.0;
                    $totalCollaboration += $metrics ? $metrics['reviews_score'] : 70.0;
                }

                $sampledCount = $sampledMembers->count();
                $productivity = round($totalProductivity / $sampledCount, 2);
                $delivery = round($totalDelivery / $sampledCount, 2);
                $attendance = round($totalAttendance / $sampledCount, 2);
                $codeQuality = round($totalCodeQuality / $sampledCount, 2);
                $collaboration = round($totalCollaboration / $sampledCount, 2);

                // Overall score = average of all 5 metrics
                $overallScore = round(($productivity + $delivery + $attendance + $codeQuality + $collaboration) / 5, 2);

                $rankings[] = [
                    'team_id' => $team->id,
                    'team_name' => $team->name,
                    'productivity' => $productivity,
                    'delivery' => $delivery,
                    'attendance' => $attendance,
                    'code_quality' => $codeQuality,
                    'collaboration' => $collaboration,
                    'overall_score' => $overallScore,
                    'members_count' => $members->count(), // Show real members count in UI
                ];
            }

            // Sort rankings descending by overall_score
            usort($rankings, function ($a, $b) {
                return $b['overall_score'] <=> $a['overall_score'];
            });

            // Add ranks
            foreach ($rankings as $index => &$rank) {
                $rank['rank'] = $index + 1;
            }
            unset($rank);

            return $rankings;
        });
    }

    /**
     * Get organization-wide leaderboards.
     */
    public function getOrganizationLeaderboard(Carbon $startDate, Carbon $endDate): array
    {
        // 1. Get all employees
        $allEmployees = User::where('role', 'employee')->with(['department', 'designation'])->get();

        // 2. Fetch bulk rankings for the entire organization
        $rankings = $this->computeBulkLeaderboard($allEmployees, $startDate, $endDate);
        
        $employeeRankings = collect($rankings)->map(function ($rank) {
            return [
                'employee_id' => $rank['employee_id'],
                'employee_name' => $rank['employee_name'],
                'department_name' => $rank['department_name'],
                'overall_score' => $rank['overall_score'],
            ];
        })->take(10)->toArray();

        foreach ($employeeRankings as $index => &$rank) {
            $rank['rank'] = $index + 1;
        }
        unset($rank);

        // 3. Top Teams (entire organization)
        $teamRankings = $this->getTeamLeaderboard(0, $startDate, $endDate, true);
        $teamRankings = array_slice($teamRankings, 0, 10);

        // 4. Top Departments (entire organization)
        $employeeScoresMap = collect($rankings)->pluck('overall_score', 'employee_id');
        $departments = \App\Models\Department::with('users')->get();
        
        $departmentRankings = [];
        foreach ($departments as $dept) {
            $deptEmployees = $dept->users->where('role', 'employee');
            if ($deptEmployees->isEmpty()) {
                continue;
            }

            $totalDeptScore = 0;
            foreach ($deptEmployees as $employee) {
                $totalDeptScore += $employeeScoresMap->get($employee->id, 0.0);
            }

            $avgDeptScore = round($totalDeptScore / $deptEmployees->count(), 2);

            $departmentRankings[] = [
                'department_id' => $dept->id,
                'department_name' => $dept->name,
                'overall_score' => $avgDeptScore,
                'employee_count' => $deptEmployees->count(),
            ];
        }
        usort($departmentRankings, function ($a, $b) {
            return $b['overall_score'] <=> $a['overall_score'];
        });
        $departmentRankings = array_slice($departmentRankings, 0, 10);
        foreach ($departmentRankings as $index => &$rank) {
            $rank['rank'] = $index + 1;
        }
        unset($rank);

        // 5. Top Contributors (Commits + Reviews)
        $contributorStats = DeveloperActivity::whereBetween('occurred_at', [$startDate, $endDate])
            ->selectRaw("user_id, 
                SUM(case when event_type = 'commit' then 1 else 0 end) as commits_count,
                SUM(case when event_type = 'review_submitted' then 1 else 0 end) as reviews_count")
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $contributorRankings = [];
        foreach ($allEmployees as $employee) {
            $stats = $contributorStats->get($employee->id);
            $commitsCount = $stats ? (int)$stats->commits_count : 0;
            $reviewsCount = $stats ? (int)$stats->reviews_count : 0;
            $totalContributions = $commitsCount + $reviewsCount;

            $contributorRankings[] = [
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'department_name' => $employee->department ? $employee->department->name : 'N/A',
                'commits' => $commitsCount,
                'reviews' => $reviewsCount,
                'total_contributions' => $totalContributions,
            ];
        }
        usort($contributorRankings, function ($a, $b) {
            return $b['total_contributions'] <=> $a['total_contributions'];
        });
        $contributorRankings = array_slice($contributorRankings, 0, 10);
        foreach ($contributorRankings as $index => &$rank) {
            $rank['rank'] = $index + 1;
        }
        unset($rank);

        return [
            'employees' => $employeeRankings,
            'teams' => $teamRankings,
            'departments' => $departmentRankings,
            'contributors' => $contributorRankings,
        ];
    }
}
