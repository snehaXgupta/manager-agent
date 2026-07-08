@extends('layouts.app')

@section('content')
<div class="space-y-8 animate-fade-in">
    <!-- Header/Back Nav -->
    <div class="space-y-4">
        <a href="{{ route('dashboard.reports.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-skyAccent dark:text-slate-400 dark:hover:text-blue-400 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to Reports Archive
        </a>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-sky-50 dark:bg-blue-950/20 text-skyAccent dark:text-blue-400 flex items-center justify-center font-bold text-lg border border-slate-200 dark:border-slate-800">
                    RP
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">
                        @if($report->report_type === 'project_completion')
                            Project Completion Report
                        @elseif($report->report_type === 'delayed_projects')
                            Delayed Projects Report
                        @elseif($report->report_type === 'team_wise_projects')
                            Team-wise Projects Report
                        @else
                            Performance Report Details
                        @endif
                    </h2>
                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                        Period: <strong class="text-slate-700 dark:text-slate-300">{{ $report->period_start->format('M d, Y') }}</strong> to <strong class="text-slate-700 dark:text-slate-300">{{ $report->period_end->format('M d, Y') }}</strong>
                    </span>
                </div>
            </div>
            <div class="text-sm text-slate-500 dark:text-slate-400 font-medium">
                Report ID: <span class="font-mono font-bold text-slate-700 dark:text-slate-350">#REP-{{ str_pad($report->id, 5, '0', STR_PAD_LEFT) }}</span>
            </div>
        </div>
    </div>

    <!-- Manager Score Card (Same radial style as Employee Developer Score) -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 shadow-sm space-y-6">
        <div class="flex flex-col lg:flex-row items-center gap-8">
            <!-- Left: Visual Score Circle -->
            <div class="flex flex-col items-center text-center space-y-4 shrink-0 pb-6 lg:pb-0 lg:pr-8 lg:border-r border-slate-200 dark:border-slate-800 w-full lg:w-auto">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">
                    @if($report->report_type === 'project_completion')
                        Project Health Score
                    @elseif($report->report_type === 'delayed_projects')
                        Average Workspace Health
                    @elseif($report->report_type === 'team_wise_projects')
                        Average Team Completion
                    @else
                        Weighted Manager Score
                    @endif
                </span>
                
                <div class="relative flex items-center justify-center">
                    <div class="absolute inset-0 rounded-full bg-gradient-to-tr from-skyAccent via-indigo-500 to-purple-600 blur-md opacity-25 dark:opacity-45 animate-pulse"></div>
                    <div class="relative w-40 h-40 rounded-full border-4 border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 flex flex-col items-center justify-center shadow-inner">
                        <span class="text-4xl font-black bg-gradient-to-r from-skyAccent via-indigo-500 to-purple-600 bg-clip-text text-transparent">
                            {{ $report->manager_score }}%
                        </span>
                        
                        @php
                            $score = $report->manager_score;
                            if ($score >= 80) {
                                $status = 'Excellent';
                                $badgeColor = 'bg-green-500/10 text-green-500 border-green-500/20';
                            } elseif ($score >= 55) {
                                $status = 'Healthy';
                                $badgeColor = 'bg-skyAccent/10 text-skyAccent border-skyAccent/20';
                            } else {
                                $status = 'Warning / At Risk';
                                $badgeColor = 'bg-rose-500/10 text-rose-500 border-rose-500/20';
                            }
                        @endphp
                        
                        <span class="mt-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeColor }} uppercase tracking-wider">
                            {{ $status }}
                        </span>
                    </div>
                </div>
                
                <div class="text-xs font-medium text-slate-405">
                    Calculation Type: <span class="font-bold text-slate-800 dark:text-slate-200">{{ str_replace('_', ' ', $report->report_type) }}</span>
                </div>
            </div>

            <!-- Right: Breakdown Components -->
            <div class="flex-1 w-full space-y-5">
                @if($report->report_type === 'project_completion')
                    <div>
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Project Workspace Assessment Breakdown</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Scorecard evaluation for project tasks, target deadline adherence, and operational risks.</p>
                    </div>

                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Task Completion -->
                            <div class="space-y-2">
                                <div class="flex justify-between items-baseline text-xs">
                                    <span class="font-bold text-slate-700 dark:text-slate-300">Task Completion Rate</span>
                                    <span class="font-extrabold text-slate-900 dark:text-white">{{ $report->metrics_json['task_completion_rate'] ?? 0 }}%</span>
                                </div>
                                <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-skyAccent to-blue-500 rounded-full" style="width: {{ $report->metrics_json['task_completion_rate'] ?? 0 }}%"></div>
                                </div>
                            </div>

                            <!-- Deadline Adherence -->
                            <div class="space-y-2">
                                <div class="flex justify-between items-baseline text-xs">
                                    <span class="font-bold text-slate-700 dark:text-slate-300">Deadline Adherence</span>
                                    <span class="font-extrabold text-slate-900 dark:text-white">{{ $report->metrics_json['deadline_adherence_rate'] ?? 0 }}%</span>
                                </div>
                                <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-teal-400 to-skyAccent rounded-full" style="width: {{ $report->metrics_json['deadline_adherence_rate'] ?? 0 }}%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-4 border-t border-slate-100 dark:border-slate-800/80">
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/30 rounded-xl text-center">
                                <span class="block text-xl font-bold text-slate-900 dark:text-white">{{ $report->metrics_json['commits_count'] ?? 0 }}</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase">Commits</span>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/30 rounded-xl text-center">
                                <span class="block text-xl font-bold text-slate-900 dark:text-white">{{ $report->metrics_json['mrs_count'] ?? 0 }}</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase">MRs</span>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/30 rounded-xl text-center animate-pulse" :class="{'bg-red-500/10': {{ $report->metrics_json['overloaded_members'] ?? 0 }} > 0}">
                                <span class="block text-xl font-bold {{ ($report->metrics_json['overloaded_members'] ?? 0) > 0 ? 'text-red-500' : 'text-slate-900 dark:text-white' }}">{{ $report->metrics_json['overloaded_members'] ?? 0 }}</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase">Overloaded</span>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/30 rounded-xl text-center">
                                <span class="block text-xl font-bold text-slate-900 dark:text-white">{{ $report->metrics_json['inactive_members'] ?? 0 }}</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase">Inactive</span>
                            </div>
                        </div>
                    </div>
                @elseif($report->report_type === 'delayed_projects')
                    <div>
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Delayed Workspaces Overview</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Aggregated statistics indicating delivery delays and critical paths.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 bg-red-50 dark:bg-red-950/20 border border-red-100 dark:border-red-900/30 rounded-2xl">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Delayed Projects Count</span>
                            <span class="text-2xl font-black text-red-650 mt-1 block">{{ $report->metrics_json['total_delayed_count'] ?? 0 }} Projects</span>
                        </div>
                        <div class="p-4 bg-slate-50 dark:bg-slate-800/30 rounded-2xl">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Average Health Index</span>
                            <span class="text-2xl font-black text-slate-900 dark:text-white mt-1 block">{{ $report->metrics_json['average_health'] ?? 0 }}%</span>
                        </div>
                    </div>
                @elseif($report->report_type === 'team_wise_projects')
                    <div>
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Teams Operational Alignment Overview</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Aggregated stats grouping project metrics by engineering teams.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 bg-slate-50 dark:bg-slate-800/30 rounded-2xl">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Teams Evaluated</span>
                            <span class="text-2xl font-black text-slate-900 dark:text-white mt-1 block">{{ $report->metrics_json['total_teams_count'] ?? 0 }} Teams</span>
                        </div>
                        <div class="p-4 bg-sky-50 dark:bg-blue-950/20 rounded-2xl">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Avg Task Completion</span>
                            <span class="text-2xl font-black text-skyAccent dark:text-blue-400 mt-1 block">{{ $report->metrics_json['average_completion_rate'] ?? 0 }}%</span>
                        </div>
                    </div>
                @else
                    <div>
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Operational Pillars Score Evaluation</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Weighted scorecard breakdown used to compute the manager score for this week.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Task Completion (40%) -->
                        <div class="space-y-2">
                            <div class="flex justify-between items-baseline text-xs">
                                <span class="font-bold text-slate-700 dark:text-slate-300">Task Completion Rate <span class="text-slate-450 font-normal">(40% weight)</span></span>
                                <span class="font-extrabold text-slate-900 dark:text-white">{{ $report->metrics_json['task_completion_rate'] ?? 0 }}%</span>
                            </div>
                            <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-skyAccent to-blue-500 rounded-full" style="width: {{ $report->metrics_json['task_completion_rate'] ?? 0 }}%"></div>
                            </div>
                            <div class="flex justify-between text-[10px] font-medium text-slate-400">
                                <span>Contribution: {{ round(($report->metrics_json['task_completion_rate'] ?? 0) * 0.40, 1) }} / 40.0 pts</span>
                                <span class="px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">Calculated</span>
                            </div>
                        </div>

                        <!-- Deadline Adherence (20%) -->
                        <div class="space-y-2">
                            <div class="flex justify-between items-baseline text-xs">
                                <span class="font-bold text-slate-700 dark:text-slate-300">Deadline Adherence <span class="text-slate-450 font-normal">(20% weight)</span></span>
                                <span class="font-extrabold text-slate-900 dark:text-white">{{ $report->metrics_json['deadline_adherence_rate'] ?? 0 }}%</span>
                            </div>
                            <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-teal-400 to-skyAccent rounded-full" style="width: {{ $report->metrics_json['deadline_adherence_rate'] ?? 0 }}%"></div>
                            </div>
                            <div class="flex justify-between text-[10px] font-medium text-slate-400">
                                <span>Contribution: {{ round(($report->metrics_json['deadline_adherence_rate'] ?? 0) * 0.20, 1) }} / 20.0 pts</span>
                                <span class="px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">Calculated</span>
                            </div>
                        </div>

                        <!-- Productivity (20%) -->
                        <div class="space-y-2">
                            <div class="flex justify-between items-baseline text-xs">
                                <span class="font-bold text-slate-700 dark:text-slate-300">Productivity Score <span class="text-slate-450 font-normal">(20% weight)</span></span>
                                <span class="font-extrabold text-slate-900 dark:text-white">{{ $report->metrics_json['productivity_score'] ?? 0 }}%</span>
                            </div>
                            <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-purple-500 to-indigo-500 rounded-full" style="width: {{ $report->metrics_json['productivity_score'] ?? 0 }}%"></div>
                            </div>
                            <div class="flex justify-between text-[10px] font-medium text-slate-400">
                                <span>Contribution: {{ round(($report->metrics_json['productivity_score'] ?? 0) * 0.20, 1) }} / 20.0 pts</span>
                                <span class="px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">Calculated</span>
                            </div>
                        </div>

                        <!-- Consistency (20%) -->
                        <div class="space-y-2">
                            <div class="flex justify-between items-baseline text-xs">
                                <span class="font-bold text-slate-700 dark:text-slate-300">Workload Consistency <span class="text-slate-450 font-normal">(20% weight)</span></span>
                                <span class="font-extrabold text-slate-900 dark:text-white">{{ $report->metrics_json['consistency_score'] ?? 0 }}%</span>
                            </div>
                            <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-pink-500 to-purple-500 rounded-full" style="width: {{ $report->metrics_json['consistency_score'] ?? 0 }}%"></div>
                            </div>
                            <div class="flex justify-between text-[10px] font-medium text-slate-400">
                                <span>Contribution: {{ round(($report->metrics_json['consistency_score'] ?? 0) * 0.20, 1) }} / 20.0 pts</span>
                                <span class="px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">Calculated</span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Performance Trend: Comparison with Previous Period -->
    @if(isset($comparison) && $comparison['has_previous'])
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 shadow-sm space-y-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-sky-50 dark:bg-blue-950/20 text-skyAccent dark:text-blue-400 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Performance Trend Comparison</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Comparing current performance metrics with the previous period report.</p>
                    </div>
                </div>
                <div>
                    <a href="{{ route('dashboard.reports.show', $comparison['previous_report_id']) }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-skyAccent hover:underline dark:text-blue-400">
                        View Previous Report
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
                @php
                    $metricDetails = [
                        'manager_score' => [
                            'label' => 'Manager Score',
                            'color' => 'indigo',
                            'suffix' => '%'
                        ],
                        'task_completion_rate' => [
                            'label' => 'Task Completion',
                            'color' => 'skyAccent',
                            'suffix' => '%'
                        ],
                        'deadline_adherence_rate' => [
                            'label' => 'Deadline Adherence',
                            'color' => 'teal',
                            'suffix' => '%'
                        ],
                        'productivity_score' => [
                            'label' => 'Productivity Score',
                            'color' => 'purple',
                            'suffix' => '%'
                        ],
                        'consistency_score' => [
                            'label' => 'Workload Consistency',
                            'color' => 'pink',
                            'suffix' => '%'
                        ]
                    ];
                @endphp

                @foreach($metricDetails as $key => $details)
                    @php
                        $mCompare = $comparison['comparison'][$key] ?? null;
                    @endphp
                    @if($mCompare)
                        @php
                            $diff = $mCompare['diff'];
                            $current = $mCompare['current'];
                            $previous = $mCompare['previous'];
                            
                            if ($diff > 0) {
                                $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900/30';
                                $arrow = '<svg class="w-3.5 h-3.5 mr-0.5 inline shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>';
                                $diffText = '+' . $diff . $details['suffix'];
                            } elseif ($diff < 0) {
                                $badgeClass = 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-900/30';
                                $arrow = '<svg class="w-3.5 h-3.5 mr-0.5 inline shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>';
                                $diffText = $diff . $details['suffix'];
                            } else {
                                $badgeClass = 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700/60';
                                $arrow = '';
                                $diffText = 'No change';
                            }
                        @endphp
                        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/30 border border-slate-100 dark:border-slate-800/40 space-y-3 flex flex-col justify-between">
                            <div class="space-y-1">
                                <span class="text-[10px] font-bold text-slate-450 dark:text-slate-400 uppercase tracking-wider block">{{ $details['label'] }}</span>
                                <div class="flex items-baseline gap-1.5 flex-wrap">
                                    <span class="text-2xl font-black text-slate-900 dark:text-white">{{ $current }}{{ $details['suffix'] }}</span>
                                    <span class="text-[10px] text-slate-450 dark:text-slate-400">prev: {{ $previous }}{{ $details['suffix'] }}</span>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between mt-auto pt-2 border-t border-slate-100 dark:border-slate-800/60">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold border {{ $badgeClass }}">
                                    {!! $arrow !!}{{ $diffText }}
                                </span>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @else
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-slate-50 dark:bg-slate-800/30 text-slate-400 rounded-xl border border-slate-200 dark:border-slate-800/60">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Performance Trend Comparison</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">No previous performance report of type <strong>{{ $report->report_type }}</strong> was found. Generate another report to see historical comparison trends.</p>
                </div>
            </div>
        </div>
    @endif

    <!-- AI Team Assessment Panel (Same styling as AI analysis on Employee detail) -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 shadow-sm space-y-6">
        <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
            <div class="p-2 bg-purple-50 dark:bg-purple-950/20 text-purple-500 rounded-xl">
                <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">AI Team Performance Assessment</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Qualitative operations analysis and resource planning insights from manager agent.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Summary & Health -->
            <div class="lg:col-span-1 space-y-4 bg-slate-50 dark:bg-slate-800/20 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/40">
                <div class="space-y-1">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block">AI Health Status</span>
                    <span class="inline-flex px-3 py-1 rounded-full text-xs font-extrabold tracking-wide uppercase border 
                        @if(($report->ai_insights_json['team_health'] ?? '') === 'Excellent')
                            bg-green-50 text-green-700 border-green-200 dark:bg-green-950/25 dark:text-green-400 dark:border-green-900/30
                        @elseif(($report->ai_insights_json['team_health'] ?? '') === 'Healthy' || ($report->ai_insights_json['team_health'] ?? '') === 'Healthy but fatigued')
                            bg-sky-50 text-skyAccent border-sky-200 dark:bg-blue-950/25 dark:text-blue-400 dark:border-blue-900/30
                        @else
                            bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/25 dark:text-rose-400 dark:border-rose-900/30
                        @endif">
                        {{ $report->ai_insights_json['team_health'] ?? 'Unrated' }}
                    </span>
                </div>
                
                <div class="space-y-1">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block">Summary</span>
                    <p class="text-xs text-slate-650 dark:text-slate-300 leading-relaxed font-medium">
                        {{ $report->ai_insights_json['summary'] ?? 'No analysis available.' }}
                    </p>
                </div>
            </div>

            <!-- Right details -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Strengths / Bottlenecks -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Strengths -->
                    <div class="space-y-3">
                        <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Team Strengths
                        </span>
                        <ul class="space-y-2">
                            @forelse($report->ai_insights_json['strengths'] ?? [] as $strength)
                                <li class="text-xs text-slate-650 dark:text-slate-355 flex items-start gap-2">
                                    <svg class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    <span>{{ $strength }}</span>
                                </li>
                            @empty
                                <li class="text-xs text-slate-400 italic">No specific strengths recorded.</li>
                            @endforelse
                        </ul>
                    </div>

                    <!-- Weaknesses -->
                    <div class="space-y-3">
                        <span class="text-xs font-bold text-amber-600 dark:text-amber-400 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            Areas to Optimize
                        </span>
                        <ul class="space-y-2">
                            @forelse($report->ai_insights_json['weaknesses'] ?? [] as $weakness)
                                <li class="text-xs text-slate-650 dark:text-slate-355 flex items-start gap-2">
                                    <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    <span>{{ $weakness }}</span>
                                </li>
                            @empty
                                <li class="text-xs text-slate-400 italic">No developmental areas recorded.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <!-- Risks & Recommendations -->
                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Risks -->
                    <div class="space-y-3">
                        <span class="text-xs font-bold text-red-600 dark:text-red-400 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                            Burnout & Backlog Risks
                        </span>
                        <ul class="space-y-2">
                            @forelse($report->ai_insights_json['risks'] ?? [] as $risk)
                                <li class="text-xs text-slate-650 dark:text-slate-355 flex items-start gap-2">
                                    <svg class="w-4 h-4 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span>{{ $risk }}</span>
                                </li>
                            @empty
                                <li class="text-xs text-slate-400 italic">No risks identified.</li>
                            @endforelse
                        </ul>
                    </div>

                    <!-- Recommendations -->
                    <div class="space-y-3">
                        <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                            Manager Action Recommendations
                        </span>
                        <ul class="space-y-2">
                            @forelse($report->ai_insights_json['recommendations'] ?? [] as $rec)
                                <li class="text-xs text-slate-650 dark:text-slate-355 flex items-start gap-2">
                                    <svg class="w-4 h-4 text-indigo-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364.364l-.707.707M21 12h-1M4 9H3m15.364 6.364l-.707-.707M6.343 6.343l.707-.707m9.9 5.05a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                    <span>{{ $rec }}</span>
                                </li>
                            @empty
                                <li class="text-xs text-slate-400 italic">No actionable recommendations.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 3: Work details of the Week -->
    @if(in_array($report->report_type, ['daily', 'weekly', 'monthly']))
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 shadow-sm space-y-6">
            <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3">Operational Stats for the Week</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="p-4 bg-slate-50 dark:bg-slate-800/30 rounded-2xl">
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Logged Team Hours</span>
                    <span class="text-2xl font-black text-slate-900 dark:text-white mt-1 block">
                        {{ $report->metrics_json['metrics_breakdown']['total_hours_logged'] ?? 0 }} hrs
                    </span>
                    <span class="text-[10px] text-slate-455">expected: {{ $report->metrics_json['metrics_breakdown']['expected_hours'] ?? 0 }} hrs</span>
                </div>

                <div class="p-4 bg-slate-50 dark:bg-slate-800/30 rounded-2xl">
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Completed Tasks</span>
                    <span class="text-2xl font-black text-slate-900 dark:text-white mt-1 block">
                        {{ $report->metrics_json['metrics_breakdown']['completed_tasks'] ?? 0 }} Tasks
                    </span>
                    <span class="text-[10px] text-slate-455">assigned total: {{ $report->metrics_json['metrics_breakdown']['total_assigned_tasks'] ?? 0 }}</span>
                </div>

                <div class="p-4 bg-slate-50 dark:bg-slate-800/30 rounded-2xl">
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tasks On Time</span>
                    <span class="text-2xl font-black text-slate-900 dark:text-white mt-1 block">
                        {{ $report->metrics_json['metrics_breakdown']['completed_on_time_tasks'] ?? 0 }} Tasks
                    </span>
                    <span class="text-[10px] text-slate-455">on-time completed</span>
                </div>

                <div class="p-4 bg-slate-50 dark:bg-slate-800/30 rounded-2xl">
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Team size</span>
                    <span class="text-2xl font-black text-slate-900 dark:text-white mt-1 block">
                        {{ $report->metrics_json['team_size'] ?? 0 }} Members
                    </span>
                    <span class="text-[10px] text-slate-455">active developers</span>
                </div>
            </div>

            <!-- Section 4: Workload Distribution Table (if exists) -->
            @if(isset($report->metrics_json['predictive']['workload']['team_workload']))
                <div class="space-y-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <h4 class="text-xs font-bold text-slate-450 uppercase tracking-wider">Workload Distribution Status</h4>
                    <div class="border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-800/40 text-slate-500 dark:text-slate-400 text-xs font-bold uppercase border-b border-slate-200 dark:border-slate-800">
                                    <th class="px-6 py-3">Employee</th>
                                    <th class="px-6 py-3 text-center">Active Tasks</th>
                                    <th class="px-6 py-3 text-center">Completed</th>
                                    <th class="px-6 py-3 text-center">Avg Duration</th>
                                    <th class="px-6 py-3 text-right">Load Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                                @foreach($report->metrics_json['predictive']['workload']['team_workload'] as $member)
                                    <tr class="hover:bg-slate-50/30 dark:hover:bg-slate-800/10 transition-colors">
                                        <td class="px-6 py-4 font-semibold text-slate-800 dark:text-slate-200">{{ $member['name'] }}</td>
                                        <td class="px-6 py-4 text-center font-bold text-slate-700 dark:text-slate-350">{{ $member['active_tasks'] }} Tasks</td>
                                        <td class="px-6 py-4 text-center text-slate-500">{{ $member['completed_tasks'] }} Tasks</td>
                                        <td class="px-6 py-4 text-center text-slate-450">{{ $member['avg_task_duration'] }} hrs/task</td>
                                        <td class="px-6 py-4 text-right">
                                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold border 
                                                @if($member['status'] === 'Overloaded')
                                                    bg-red-50 border-red-200 text-red-700 dark:bg-red-950/20 dark:border-red-800 dark:text-red-400
                                                @elseif($member['status'] === 'Underutilized')
                                                    bg-amber-50 border-amber-200 text-amber-700 dark:bg-amber-950/20 dark:border-amber-800 dark:text-amber-400
                                                @else
                                                    bg-green-50 border-green-200 text-green-700 dark:bg-green-950/20 dark:border-green-800 dark:text-green-400
                                                @endif">
                                                {{ $member['status'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @elseif($report->report_type === 'delayed_projects')
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 shadow-sm space-y-4">
            <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3">Delayed & At-Risk Projects</h3>
            <div class="border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/40 text-slate-500 dark:text-slate-400 text-xs font-bold uppercase border-b border-slate-200 dark:border-slate-800">
                            <th class="px-6 py-3">Project Name</th>
                            <th class="px-6 py-3">Category</th>
                            <th class="px-6 py-3 text-center">Target Deadline</th>
                            <th class="px-6 py-3 text-center">Overdue Tasks</th>
                            <th class="px-6 py-3 text-right">Health Score</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                        @forelse($report->metrics_json['delayed_projects'] ?? [] as $proj)
                            <tr class="hover:bg-slate-50/30 dark:hover:bg-slate-800/10 transition-colors">
                                <td class="px-6 py-4 font-semibold text-slate-800 dark:text-slate-200">
                                    <a href="{{ route('dashboard.projects.show', $proj['id']) }}" class="text-skyAccent hover:underline dark:text-blue-400">
                                        {{ $proj['name'] }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-slate-500">{{ $proj['category'] ?? 'Development' }}</td>
                                <td class="px-6 py-4 text-center text-slate-500">{{ $proj['deadline'] ? \Carbon\Carbon::parse($proj['deadline'])->format('M d, Y') : 'No Target' }}</td>
                                <td class="px-6 py-4 text-center font-bold text-red-650">{{ $proj['overdue_tasks'] }} Overdue</td>
                                <td class="px-6 py-4 text-right">
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold border 
                                        @if($proj['health_score'] >= 80)
                                            bg-green-50 border-green-200 text-green-700 dark:bg-green-950/20 dark:border-green-800 dark:text-green-400
                                        @elseif($proj['health_score'] >= 50)
                                            bg-amber-50 border-amber-200 text-amber-700 dark:bg-amber-950/20 dark:border-amber-800 dark:text-amber-400
                                        @else
                                            bg-red-50 border-red-200 text-red-700 dark:bg-red-950/20 dark:border-red-800 dark:text-red-400
                                        @endif">
                                        {{ $proj['health_score'] }}% Health
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400 italic">No delayed projects found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @elseif($report->report_type === 'team_wise_projects')
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 shadow-sm space-y-4">
            <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3">Team Performance & Project Mapping</h3>
            <div class="border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/40 text-slate-500 dark:text-slate-400 text-xs font-bold uppercase border-b border-slate-200 dark:border-slate-800">
                            <th class="px-6 py-3">Team Name</th>
                            <th class="px-6 py-3 text-center">Total Tasks</th>
                            <th class="px-6 py-3 text-center">Completed Tasks</th>
                            <th class="px-6 py-3 text-center">Completion Rate</th>
                            <th class="px-6 py-3 text-right">Contributing Projects</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                        @forelse($report->metrics_json['teams_data'] ?? [] as $tData)
                            <tr class="hover:bg-slate-50/30 dark:hover:bg-slate-800/10 transition-colors">
                                <td class="px-6 py-4 font-semibold text-slate-800 dark:text-slate-200">
                                    <a href="{{ route('dashboard.teams.show', $tData['team_id']) }}" class="text-skyAccent hover:underline dark:text-blue-400">
                                        {{ $tData['team_name'] }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-center text-slate-500">{{ $tData['total_tasks'] }} Tasks</td>
                                <td class="px-6 py-4 text-center text-slate-500">{{ $tData['completed_tasks'] }} Completed</td>
                                <td class="px-6 py-4 text-center font-bold text-skyAccent dark:text-blue-400">{{ $tData['completion_rate'] }}%</td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex flex-wrap gap-1 justify-end">
                                        @forelse($tData['projects'] ?? [] as $pName)
                                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-655 dark:text-slate-400">{{ $pName }}</span>
                                        @empty
                                            <span class="text-slate-400 italic text-[10px]">None</span>
                                        @endforelse
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400 italic">No team metrics recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
