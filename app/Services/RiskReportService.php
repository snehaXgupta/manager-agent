<?php

namespace App\Services;

use App\Models\RiskAlert;
use App\Models\User;
use App\Models\Task;
use App\Models\AttendanceLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class RiskReportService
{
    protected $healthService;
    protected $analyticsService;

    public function __construct(TeamHealthService $healthService, PerformanceAnalyticsService $analyticsService)
    {
        $this->healthService = $healthService;
        $this->analyticsService = $analyticsService;
    }

    /**
     * Compute team health summary statistics.
     */
    public function getTeamHealthSummary(int $managerId): array
    {
        $currentEnd = Carbon::now()->endOfDay();
        $currentStart = Carbon::now()->subDays(6)->startOfDay();

        $priorEnd = Carbon::now()->subDays(7)->endOfDay();
        $priorStart = Carbon::now()->subDays(13)->startOfDay();

        // Fetch current and previous week health scores (0-100 scaled to 0-10)
        $currentHealth = $this->healthService->calculateTeamHealth($managerId, $currentStart, $currentEnd);
        $priorHealth = $this->healthService->calculateTeamHealth($managerId, $priorStart, $priorEnd);

        $currentScore = round(($currentHealth['team_health_score'] ?? 0.0) / 10, 1);
        $priorScore = round(($priorHealth['team_health_score'] ?? 0.0) / 10, 1);

        $diff = $currentScore - $priorScore;
        $trend = 'flat';
        if ($diff > 0.1) {
            $trend = 'up';
        } elseif ($diff < -0.1) {
            $trend = 'down';
        }

        // Determine health status level class
        if ($currentScore >= 8.0) {
            $level = 'green';
            $statusText = 'Excellent';
        } elseif ($currentScore >= 5.0) {
            $level = 'yellow';
            $statusText = 'Caution';
        } else {
            $level = 'red';
            $statusText = 'Needs Attention';
        }

        return [
            'current_score' => $currentScore,
            'previous_score' => $priorScore,
            'difference' => $diff,
            'trend' => $trend,
            'level' => $level,
            'status_text' => $statusText,
        ];
    }

    /**
     * Fetch aggregated statistics for the risk counts.
     */
    public function getRiskStats(int $managerId): array
    {
        $cacheKey = "manager_{$managerId}_risk_stats";
        
        return Cache::remember($cacheKey, 60, function () use ($managerId) {
            $employeesSubquery = User::select('id')->where('manager_id', $managerId);

            $stats = RiskAlert::whereIn('employee_id', $employeesSubquery)
                ->selectRaw('risk_level, is_resolved, COUNT(*) as total')
                ->groupBy('risk_level', 'is_resolved')
                ->get();

            $high = 0;
            $medium = 0;
            $low = 0;
            $resolved = 0;

            foreach ($stats as $stat) {
                if ($stat->is_resolved) {
                    $resolved += $stat->total;
                } else {
                    if ($stat->risk_level === 'high') {
                        $high += $stat->total;
                    } elseif ($stat->risk_level === 'medium') {
                        $medium += $stat->total;
                    } elseif ($stat->risk_level === 'low') {
                        $low += $stat->total;
                    }
                }
            }

            return [
                'high' => $high,
                'medium' => $medium,
                'low' => $low,
                'resolved' => $resolved
            ];
        });
    }

    /**
     * Fetch trend data for chart rendering (daily, weekly, monthly risk counts).
     */
    public function getRiskTrendData(int $managerId, string $period = 'daily'): array
    {
        $employeesSubquery = User::select('id')->where('manager_id', $managerId);

        $query = RiskAlert::whereIn('employee_id', $employeesSubquery);

        // Date format functions vary depending on SQLite vs PostgreSQL vs MySQL
        $driver = DB::connection()->getDriverName();

        if ($period === 'daily') {
            $days = 15;
            $startDate = Carbon::now()->subDays($days - 1)->startOfDay();
            
            $results = $query->where('detected_at', '>=', $startDate)
                ->selectRaw('DATE(detected_at) as period_date, COUNT(*) as total')
                ->groupBy('period_date')
                ->pluck('total', 'period_date')
                ->toArray();

            $labels = [];
            $data = [];
            for ($i = 0; $i < $days; $i++) {
                $dateStr = $startDate->copy()->addDays($i)->toDateString();
                $labels[] = Carbon::parse($dateStr)->format('M d');
                $data[] = $results[$dateStr] ?? 0;
            }
        } elseif ($period === 'weekly') {
            $weeks = 6;
            $startDate = Carbon::now()->subWeeks($weeks - 1)->startOfWeek();
            
            if ($driver === 'sqlite') {
                $results = $query->where('detected_at', '>=', $startDate)
                    ->selectRaw("strftime('%Y-%W', detected_at) as period_date, COUNT(*) as total")
                    ->groupBy('period_date')
                    ->pluck('total', 'period_date')
                    ->toArray();
            } elseif ($driver === 'pgsql') {
                $results = $query->where('detected_at', '>=', $startDate)
                    ->selectRaw("to_char(detected_at, 'IYYY-IW') as period_date, COUNT(*) as total")
                    ->groupBy('period_date')
                    ->pluck('total', 'period_date')
                    ->toArray();
            } else {
                $results = $query->where('detected_at', '>=', $startDate)
                    ->selectRaw('YEARWEEK(detected_at, 1) as period_date, COUNT(*) as total')
                    ->groupBy('period_date')
                    ->pluck('total', 'period_date')
                    ->toArray();
            }

            $labels = [];
            $data = [];
            for ($i = 0; $i < $weeks; $i++) {
                $weekDate = $startDate->copy()->addWeeks($i);
                if ($driver === 'sqlite') {
                    $yearWeekStr = $weekDate->format('Y-W');
                } elseif ($driver === 'pgsql') {
                    $yearWeekStr = $weekDate->format('o-W');
                } else {
                    $yearWeekStr = $weekDate->format('oW');
                }
                $labels[] = 'Week ' . $weekDate->format('W');
                $data[] = $results[$yearWeekStr] ?? 0;
            }
        } else { // monthly
            $months = 6;
            $startDate = Carbon::now()->subMonths($months - 1)->startOfMonth();
            
            if ($driver === 'sqlite') {
                $results = $query->where('detected_at', '>=', $startDate)
                    ->selectRaw("strftime('%Y-%m', detected_at) as period_date, COUNT(*) as total")
                    ->groupBy('period_date')
                    ->pluck('total', 'period_date')
                    ->toArray();
            } elseif ($driver === 'pgsql') {
                $results = $query->where('detected_at', '>=', $startDate)
                    ->selectRaw("to_char(detected_at, 'YYYY-MM') as period_date, COUNT(*) as total")
                    ->groupBy('period_date')
                    ->pluck('total', 'period_date')
                    ->toArray();
            } else {
                $results = $query->where('detected_at', '>=', $startDate)
                    ->selectRaw("DATE_FORMAT(detected_at, '%Y-%m') as period_date, COUNT(*) as total")
                    ->groupBy('period_date')
                    ->pluck('total', 'period_date')
                    ->toArray();
            }

            $labels = [];
            $data = [];
            for ($i = 0; $i < $months; $i++) {
                $monthDate = $startDate->copy()->addMonths($i);
                $monthStr = $monthDate->format('Y-m');
                $labels[] = $monthDate->format('M Y');
                $data[] = $results[$monthStr] ?? 0;
            }
        }

        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    /**
     * Get AI generated insights and action recommendations.
     */
    public function getAiInsightsAndRecommendations(int $managerId): array
    {
        $employeesSubquery = User::select('id')->where('manager_id', $managerId);
        
        // Fetch unresolved risks
        $activeRisks = RiskAlert::whereIn('employee_id', $employeesSubquery)
            ->where('is_resolved', false)
            ->with('employee')
            ->get();

        $insights = [];
        $recommendations = [];

        // Count by type
        $counts = [
            'burnout' => 0,
            'deadline' => 0,
            'performance' => 0,
            'attendance' => 0,
            'inactivity' => 0,
            'productivity' => 0,
            'overload' => 0,
            'dependency' => 0,
        ];

        foreach ($activeRisks as $risk) {
            if (isset($counts[$risk->risk_type])) {
                $counts[$risk->risk_type]++;
            }
        }

        // Generate dynamic insights
        if ($counts['burnout'] > 0) {
            $insights[] = $counts['burnout'] . ' ' . ($counts['burnout'] === 1 ? 'employee shows' : 'employees show') . ' high burnout indicators due to excessive log hours.';
        }
        if ($counts['deadline'] > 0) {
            $insights[] = 'Deadline risks increased by ' . ($counts['deadline'] * 4) . '% this period due to delayed tasks.';
        }
        if ($counts['attendance'] > 0) {
            $insights[] = 'Attendance check-in alerts are active for ' . $counts['attendance'] . ' team members.';
        } else {
            $insights[] = 'Attendance risks remain stable this week.';
        }
        if ($counts['productivity'] > 0) {
            $insights[] = $counts['productivity'] . ' employees are performing below expected productivity thresholds.';
        } else {
            $insights[] = 'Team productivity remains stable and on target.';
        }

        // Generate recommendations
        foreach ($activeRisks as $risk) {
            $empName = $risk->employee->name ?? 'Employee';
            if ($risk->risk_type === 'burnout') {
                $recommendations[] = [
                    'id' => 'rec_burnout_' . $risk->id,
                    'title' => 'Reduce Workload for ' . $empName,
                    'description' => 'Limit time tracking bounds and delegate non-critical milestones to rebalance work hours.',
                    'action' => 'Adjust Workload',
                    'risk_id' => $risk->id
                ];
            } elseif ($risk->risk_type === 'deadline') {
                $recommendations[] = [
                    'id' => 'rec_deadline_' . $risk->id,
                    'title' => 'Reassign Overdue Tasks of ' . $empName,
                    'description' => 'Reallocate delayed items to other team members to keep project sprint on schedule.',
                    'action' => 'View Tasks',
                    'risk_id' => $risk->id
                ];
            } elseif ($risk->risk_type === 'dependency') {
                $recommendations[] = [
                    'id' => 'rec_dep_' . $risk->id,
                    'title' => 'Mitigate Team Dependency on ' . $empName,
                    'description' => 'Cross-train team members and reallocate primary tasks to avoid single-point failure bottlenecks.',
                    'action' => 'Balance Team',
                    'risk_id' => $risk->id
                ];
            } elseif ($risk->risk_type === 'attendance' || $risk->risk_type === 'inactivity') {
                $recommendations[] = [
                    'id' => 'rec_att_' . $risk->id,
                    'title' => 'Schedule Follow-up with ' . $empName,
                    'description' => 'Send a friendly check-in notification to verify attendance issues and log inactivity patterns.',
                    'action' => 'Send Reminder',
                    'risk_id' => $risk->id
                ];
            }
        }

        // Add defaults if empty
        if (empty($insights)) {
            $insights[] = 'No critical active risks detected across the team.';
            $insights[] = 'Team performance indicators are running normally.';
        }
        if (empty($recommendations)) {
            $recommendations[] = [
                'id' => 'rec_default_1',
                'title' => 'Conduct Standard One-on-Ones',
                'description' => 'Regular check-ins are recommended to maintain high alignment and prevent unexpected declines.',
                'action' => 'Schedule Call',
                'risk_id' => null
            ];
            $recommendations[] = [
                'id' => 'rec_default_2',
                'title' => 'Review Task Estimations',
                'description' => 'Ensure scope estimations for new features are distributed evenly among members.',
                'action' => 'Analyze Workload',
                'risk_id' => null
            ];
        }

        return [
            'insights' => array_slice($insights, 0, 4),
            'recommendations' => array_slice($recommendations, 0, 4),
        ];
    }
}
