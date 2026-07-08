<?php

namespace App\Services;

use App\Models\PerformanceReport;
use App\Models\User;
use Illuminate\Support\Carbon;
use Exception;

class ReportService
{
    protected $analyticsService;
    protected $ollamaAiService;

    public function __construct(PerformanceAnalyticsService $analyticsService, OllamaAiService $ollamaAiService)
    {
        $this->analyticsService = $analyticsService;
        $this->ollamaAiService = $ollamaAiService;
    }

    /**
     * Generate and save a new performance report for a manager.
     *
     * @param int $managerId
     * @param string $reportType 'daily'|'weekly'|'monthly'
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return PerformanceReport
     * @throws Exception
     */
    public function generateReport(int $managerId, string $reportType, ?Carbon $startDate = null, ?Carbon $endDate = null, ?int $projectId = null): PerformanceReport
    {
        // Validate user existence
        $user = User::find($managerId);
        if (!$user) {
            throw new Exception("User with ID {$managerId} not found.");
        }

        // Determine default date ranges if null
        if (!$endDate) {
            $endDate = Carbon::now()->endOfDay();
        }
        if (!$startDate) {
            if ($reportType === 'daily') {
                $startDate = Carbon::now()->startOfDay();
            } elseif ($reportType === 'monthly') {
                $startDate = Carbon::now()->subDays(29)->startOfDay();
            } else { // default to weekly
                $startDate = Carbon::now()->subDays(6)->startOfDay();
            }
        }

        if ($user->role === 'manager') {
            // Check for specialized report types
            if ($reportType === 'project_completion') {
                return $this->generateProjectCompletionReport($managerId, $projectId ?: 0, $startDate, $endDate);
            } elseif ($reportType === 'delayed_projects') {
                return $this->generateDelayedProjectsReport($managerId, $startDate, $endDate);
            } elseif ($reportType === 'team_wise_projects') {
                return $this->generateTeamWiseProjectsReport($managerId, $startDate, $endDate);
            }

            // 1. Calculate deterministic performance metrics for manager's team
            $metrics = $this->analyticsService->calculateTeamMetrics($managerId, $startDate, $endDate);

            // 2. Compile Predictive Intelligence for manager's team
            $teamHealth = app(TeamHealthService::class)->calculateTeamHealth($managerId, $startDate, $endDate, $metrics);
            $workloadRaw = app(WorkloadAnalysisService::class)->analyzeWorkload($managerId, 15);
            $workload = [
                'team_workload' => $workloadRaw['team_workload'],
                'recommendations' => $workloadRaw['recommendations']
            ];
            $activeRisks = app(RiskDetectionService::class)->getActiveRisksForManager($managerId)
                ->take(15)
                ->map(function ($risk) {
                    return [
                        'id' => $risk->id,
                        'employee_id' => $risk->employee_id,
                        'risk_level' => $risk->risk_level,
                        'risk_type' => $risk->risk_type,
                        'reason' => $risk->reason,
                        'metrics_json' => $risk->metrics_json,
                        'detected_at' => $risk->detected_at,
                        'is_resolved' => $risk->is_resolved,
                        'employee' => [
                            'id' => $risk->employee?->id,
                            'name' => $risk->employee?->name,
                            'email' => $risk->employee?->email,
                        ]
                    ];
                })
                ->toArray();

            // 3. Fetch AI Insights from Ollama Agent
            $aiInsights = $this->ollamaAiService->generateInsights($metrics);
            $predictiveAi = $this->ollamaAiService->generatePredictiveInsights($teamHealth, $activeRisks, $workload);

            // Merge into structured JSON format
            $metrics['predictive'] = [
                'team_health' => $teamHealth,
                'workload' => $workload,
                'risks' => $activeRisks
            ];
            $aiInsights['predictive_ai'] = $predictiveAi;
            $score = $metrics['manager_score'];
        } else {
            // Individual employee report
            // 1. Calculate deterministic performance metrics for employee
            $metrics = $this->analyticsService->calculateUserMetrics($managerId, $startDate, $endDate);

            // 2. Compile Predictive Intelligence (individual active risk alerts)
            $activeRisksRaw = app(RiskDetectionService::class)->detectUserRisks($managerId);
            $activeRisks = collect($activeRisksRaw)->map(function ($risk) {
                return [
                    'id' => $risk->id,
                    'employee_id' => $risk->employee_id,
                    'risk_level' => $risk->risk_level,
                    'risk_type' => $risk->risk_type,
                    'reason' => $risk->reason,
                    'metrics_json' => $risk->metrics_json,
                    'detected_at' => $risk->detected_at,
                    'is_resolved' => $risk->is_resolved,
                    'employee' => [
                        'id' => $risk->employee?->id,
                        'name' => $risk->employee?->name,
                        'email' => $risk->employee?->email,
                    ]
                ];
            })->toArray();

            // 3. Fetch AI insights specifically for employee
            $aiInsights = $this->ollamaAiService->generateEmployeeInsights($user, $metrics);

            // Merge into structured JSON format
            $metrics['predictive'] = [
                'risks' => $activeRisks
            ];
            $score = $metrics['developer_score'] ?? $metrics['manager_score'];
        }

        // 4. Persist report
        $report = PerformanceReport::create([
            'manager_id' => $managerId,
            'report_type' => $reportType,
            'period_start' => $startDate,
            'period_end' => $endDate,
            'metrics_json' => $metrics,
            'ai_insights_json' => $aiInsights,
            'manager_score' => $score,
            'generated_at' => Carbon::now(),
        ]);

        // Clear leaderboard cache when a new report is generated
        app(LeaderboardService::class)->clearCache($reportType);

        return $report;
    }

    /**
     * Fetch historical reports for a manager.
     */
    public function getHistoricalReports(int $managerId)
    {
        return PerformanceReport::where('manager_id', $managerId)
            ->orderBy('generated_at', 'desc')
            ->get();
    }

    /**
     * Fetch a specific report.
     */
    public function getReportById(int $reportId): ?PerformanceReport
    {
        return PerformanceReport::find($reportId);
    }

    /**
     * Compare a report with the previous period's report.
     *
     * @param PerformanceReport $report
     * @return array
     */
    public function compareWithPrevious(PerformanceReport $report): array
    {
        // Find previous report of the same type for this manager
        $previousReport = PerformanceReport::where('manager_id', $report->manager_id)
            ->where('report_type', $report->report_type)
            ->where('generated_at', '<', $report->generated_at)
            ->orderBy('generated_at', 'desc')
            ->first();

        if (!$previousReport) {
            return [
                'has_previous' => false,
                'comparison' => []
            ];
        }

        $currentMetrics = $report->metrics_json;
        $prevMetrics = $previousReport->metrics_json;

        $comparison = [
            'manager_score' => [
                'current' => $report->manager_score,
                'previous' => $previousReport->manager_score,
                'diff' => round($report->manager_score - $previousReport->manager_score, 2),
            ],
            'task_completion_rate' => [
                'current' => $currentMetrics['task_completion_rate'] ?? 0,
                'previous' => $prevMetrics['task_completion_rate'] ?? 0,
                'diff' => round(($currentMetrics['task_completion_rate'] ?? 0) - ($prevMetrics['task_completion_rate'] ?? 0), 2),
            ],
            'deadline_adherence_rate' => [
                'current' => $currentMetrics['deadline_adherence_rate'] ?? 0,
                'previous' => $prevMetrics['deadline_adherence_rate'] ?? 0,
                'diff' => round(($currentMetrics['deadline_adherence_rate'] ?? 0) - ($prevMetrics['deadline_adherence_rate'] ?? 0), 2),
            ],
            'productivity_score' => [
                'current' => $currentMetrics['productivity_score'] ?? 0,
                'previous' => $prevMetrics['productivity_score'] ?? 0,
                'diff' => round(($currentMetrics['productivity_score'] ?? 0) - ($prevMetrics['productivity_score'] ?? 0), 2),
            ],
            'consistency_score' => [
                'current' => $currentMetrics['consistency_score'] ?? 0,
                'previous' => $prevMetrics['consistency_score'] ?? 0,
                'diff' => round(($currentMetrics['consistency_score'] ?? 0) - ($prevMetrics['consistency_score'] ?? 0), 2),
            ]
        ];

        return [
            'has_previous' => true,
            'previous_report_id' => $previousReport->id,
            'comparison' => $comparison
        ];
    }

    /**
     * Generate Project Completion Report.
     */
    protected function generateProjectCompletionReport(int $managerId, int $projectId, Carbon $startDate, Carbon $endDate): PerformanceReport
    {
        $project = \App\Models\Project::where('id', $projectId)
            ->where('manager_id', $managerId)
            ->with(['members', 'repository'])
            ->firstOrFail();

        $allTasksQuery = \App\Models\Task::where('project_id', $projectId);
        $totalTasks = $allTasksQuery->count();
        $completedTasksCount = (clone $allTasksQuery)->where('status', 'completed')->count();
        $taskCompletionRate = $totalTasks > 0 ? ($completedTasksCount / $totalTasks) * 100 : 100.0;

        $completedOnTimeCount = (clone $allTasksQuery)->where('status', 'completed')
            ->where(function ($query) {
                $query->whereNull('deadline')
                      ->orWhereColumn('updated_at', '<=', 'deadline');
            })
            ->count();
        $deadlineAdherenceRate = $completedTasksCount > 0 
            ? ($completedOnTimeCount / $completedTasksCount) * 100 
            : 100.0;

        $commitsCount = \App\Models\Commit::where('project_id', $projectId)->count();
        $mrsCount = \App\Models\MergeRequest::where('project_id', $projectId)->count();

        // Project Health calculation
        $progressPoints = ($taskCompletionRate / 100) * 40;
        if ($project->deadline) {
            $deadline = Carbon::parse($project->deadline);
            if (Carbon::now()->gt($deadline) && $project->status !== 'completed') {
                $deadlinePoints = 0;
            } elseif ($project->status === 'completed') {
                $deadlinePoints = 40;
            } else {
                $overduePendingTasks = \App\Models\Task::where('project_id', $projectId)
                    ->where('status', '!=', 'completed')
                    ->where('deadline', '<', Carbon::now())
                    ->count();
                $deadlinePoints = 40 - ($overduePendingTasks > 0 ? min(40, ($overduePendingTasks / max(1, $totalTasks)) * 40) : 0);
            }
        } else {
            $deadlinePoints = ($deadlineAdherenceRate / 100) * 40;
        }

        $overloadedMembersCount = 0;
        $inactiveMembersCount = 0;
        foreach ($project->members as $member) {
            $memberTasksCount = \App\Models\Task::where('project_id', $projectId)
                ->where('assigned_to', $member->id)
                ->where('status', '!=', 'completed')
                ->count();
            if ($memberTasksCount > 5) {
                $overloadedMembersCount++;
            }
            $memberTotalTasksCount = \App\Models\Task::where('project_id', $projectId)
                ->where('assigned_to', $member->id)
                ->count();
            if ($memberTotalTasksCount === 0) {
                $inactiveMembersCount++;
            }
        }
        $overloadPenalty = $overloadedMembersCount * 5;
        $inactivityPenalty = $inactiveMembersCount * 3;

        $gitInactivityPenalty = 0;
        if ($project->repository) {
            $latestCommit = \App\Models\Commit::where('project_id', $projectId)
                ->orderBy('committed_at', 'desc')
                ->first();
            if (!$latestCommit || Carbon::parse($latestCommit->committed_at)->lt(Carbon::now()->subDays(5))) {
                $gitInactivityPenalty = 10;
            }
        }

        $riskPenalties = $overloadPenalty + $inactivityPenalty + $gitInactivityPenalty;
        $riskPoints = max(0, 20 - $riskPenalties);
        $healthScore = round($progressPoints + $deadlinePoints + $riskPoints);

        $metrics = [
            'project_id' => $projectId,
            'project_name' => $project->name,
            'project_description' => $project->description,
            'category' => $project->category,
            'status' => $project->status,
            'deadline' => $project->deadline ? $project->deadline->toDateString() : null,
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasksCount,
            'task_completion_rate' => round($taskCompletionRate, 2),
            'deadline_adherence_rate' => round($deadlineAdherenceRate, 2),
            'commits_count' => $commitsCount,
            'mrs_count' => $mrsCount,
            'health_score' => $healthScore,
            'overloaded_members' => $overloadedMembersCount,
            'inactive_members' => $inactiveMembersCount,
        ];

        $aiInsights = [
            'summary' => "AI Assessment for Project: {$project->name}. The project is currently at a health score of {$healthScore}% with {$taskCompletionRate}% task completion.",
            'strengths' => [
                "Overall health score of {$healthScore}%",
                "Task completion rate stands at " . round($taskCompletionRate, 1) . "%"
            ],
            'weaknesses' => $overloadedMembersCount > 0 ? ["{$overloadedMembersCount} team members are currently overloaded with tasks."] : ["No critical resource bottlenecks detected."],
            'risks' => $gitInactivityPenalty > 0 ? ["No commits have been detected in the last 5 days."] : ["GitLab commit cadence is active."],
            'recommendations' => [
                "Align with the assigned members to resolve any outstanding blockers.",
                "Ensure GitLab check-ins are updated to verify continuous integration progress."
            ],
            'team_health' => $healthScore >= 80 ? 'Excellent' : ($healthScore >= 50 ? 'Needs Attention' : 'At Risk')
        ];

        return PerformanceReport::create([
            'manager_id' => $managerId,
            'report_type' => 'project_completion',
            'period_start' => $startDate,
            'period_end' => $endDate,
            'metrics_json' => $metrics,
            'ai_insights_json' => $aiInsights,
            'manager_score' => $healthScore,
            'generated_at' => Carbon::now(),
        ]);
    }

    /**
     * Generate Delayed Projects Report.
     */
    protected function generateDelayedProjectsReport(int $managerId, Carbon $startDate, Carbon $endDate): PerformanceReport
    {
        $projects = \App\Models\Project::where('manager_id', $managerId)
            ->where('is_archived', false)
            ->with(['members'])
            ->get();

        $delayedProjects = [];
        $totalHealth = 0;
        $count = 0;

        foreach ($projects as $project) {
            $allTasksQuery = \App\Models\Task::where('project_id', $project->id);
            $totalTasks = $allTasksQuery->count();
            $completedTasksCount = (clone $allTasksQuery)->where('status', 'completed')->count();
            $taskCompletionRate = $totalTasks > 0 ? ($completedTasksCount / $totalTasks) * 100 : 100.0;

            $completedOnTimeCount = (clone $allTasksQuery)->where('status', 'completed')
                ->where(function ($query) {
                    $query->whereNull('deadline')
                          ->orWhereColumn('updated_at', '<=', 'deadline');
                })
                ->count();
            $deadlineAdherenceRate = $completedTasksCount > 0 
                ? ($completedOnTimeCount / $completedTasksCount) * 100 
                : 100.0;

            $isPastDeadline = false;
            if ($project->deadline) {
                $deadline = Carbon::parse($project->deadline);
                if (Carbon::now()->gt($deadline) && $project->status !== 'completed') {
                    $isPastDeadline = true;
                }
            }

            $overdueTasksCount = \App\Models\Task::where('project_id', $project->id)
                ->where('status', '!=', 'completed')
                ->where('deadline', '<', Carbon::now())
                ->count();

            $progressPoints = ($taskCompletionRate / 100) * 40;
            $deadlinePoints = $isPastDeadline ? 0 : 40;
            $healthScore = round($progressPoints + $deadlinePoints + 20);

            if ($isPastDeadline || $overdueTasksCount > 0 || $healthScore < 70) {
                $delayedProjects[] = [
                    'id' => $project->id,
                    'name' => $project->name,
                    'category' => $project->category,
                    'status' => $project->status,
                    'deadline' => $project->deadline ? $project->deadline->toDateString() : null,
                    'total_tasks' => $totalTasks,
                    'completed_tasks' => $completedTasksCount,
                    'overdue_tasks' => $overdueTasksCount,
                    'health_score' => $healthScore,
                ];
                $totalHealth += $healthScore;
                $count++;
            }
        }

        $averageHealth = $count > 0 ? round($totalHealth / $count, 2) : 100.0;

        $metrics = [
            'delayed_projects' => $delayedProjects,
            'total_delayed_count' => $count,
            'average_health' => $averageHealth,
        ];

        $aiInsights = [
            'summary' => "AI Assessment: Identified {$count} delayed or high-risk projects. The average health score of these workspaces is {$averageHealth}%.",
            'strengths' => ["Core infrastructure settings are correct across project configurations."],
            'weaknesses' => ["{$count} project(s) have slipped past deadline or accumulated overdue tasks."],
            'risks' => ["Potential milestone failure or overall SLA slippage if task backlogs are not addressed."],
            'recommendations' => [
                "Re-estimate remaining tasks on delayed items.",
                "Redistribute pending tasks from overloaded developers to other team members."
            ],
            'team_health' => $averageHealth >= 80 ? 'Excellent' : ($averageHealth >= 50 ? 'Needs Attention' : 'At Risk')
        ];

        return PerformanceReport::create([
            'manager_id' => $managerId,
            'report_type' => 'delayed_projects',
            'period_start' => $startDate,
            'period_end' => $endDate,
            'metrics_json' => $metrics,
            'ai_insights_json' => $aiInsights,
            'manager_score' => $averageHealth,
            'generated_at' => Carbon::now(),
        ]);
    }

    /**
     * Generate Team-wise Project Report.
     */
    protected function generateTeamWiseProjectsReport(int $managerId, Carbon $startDate, Carbon $endDate): PerformanceReport
    {
        $teams = \App\Models\Team::where('manager_id', $managerId)
            ->with(['members'])
            ->get();

        $teamsData = [];
        $totalCompletionRate = 0;
        $count = 0;

        foreach ($teams as $team) {
            $tasksQuery = \App\Models\Task::where('team_id', $team->id);
            $totalTasks = $tasksQuery->count();
            $completedTasksCount = (clone $tasksQuery)->where('status', 'completed')->count();
            $completionRate = $totalTasks > 0 ? ($completedTasksCount / $totalTasks) * 100 : 100.0;

            $projectIds = \App\Models\Task::where('team_id', $team->id)
                ->whereNotNull('project_id')
                ->distinct()
                ->pluck('project_id');
            
            $projectsList = \App\Models\Project::whereIn('id', $projectIds)->pluck('name')->toArray();

            $teamsData[] = [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasksCount,
                'completion_rate' => round($completionRate, 2),
                'projects' => $projectsList,
            ];

            $totalCompletionRate += $completionRate;
            $count++;
        }

        $averageCompletion = $count > 0 ? round($totalCompletionRate / $count, 2) : 100.0;

        $metrics = [
            'teams_data' => $teamsData,
            'total_teams_count' => $count,
            'average_completion_rate' => $averageCompletion,
        ];

        $aiInsights = [
            'summary' => "AI Assessment: Compiled metrics for {$count} teams. Average task completion rate across teams is {$averageCompletion}%.",
            'strengths' => ["Teams are successfully mapped to their respective project tasks."],
            'weaknesses' => ["Varying completion rates indicate uneven task pacing or workload imbalance between teams."],
            'risks' => ["Cross-team dependency blocks if one team lags on task completions."],
            'recommendations' => [
                "Review workload and redistribute tasks between high-load and low-load teams.",
                "Ensure team meetings address project integration checkpoints."
            ],
            'team_health' => $averageCompletion >= 80 ? 'Excellent' : ($averageCompletion >= 50 ? 'Needs Attention' : 'At Risk')
        ];

        return PerformanceReport::create([
            'manager_id' => $managerId,
            'report_type' => 'team_wise_projects',
            'period_start' => $startDate,
            'period_end' => $endDate,
            'metrics_json' => $metrics,
            'ai_insights_json' => $aiInsights,
            'manager_score' => $averageCompletion,
            'generated_at' => Carbon::now(),
        ]);
    }
}
