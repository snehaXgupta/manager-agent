@extends('layouts.app')

@section('content')
<div class="space-y-8 animate-fade-in">
    <!-- Header Summary -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Team Performance Overview</h2>
            <!-- <p class="text-sm text-slate-500 dark:text-slate-400">Welcome back, {{ $manager->name }}. Here is how your team is performing this week.</p> -->
        </div>
        <div class="px-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
            <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">Live Telemetry Active</span>
        </div>
    </div>


    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Members Card -->
        <div class="group bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-skyAccent/40 dark:hover:border-skyAccent/40 p-6 rounded-2xl shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between relative overflow-hidden">
            <div class="absolute -top-6 -right-6 w-36 h-36 bg-gradient-to-br from-skyAccent/10 to-blue-500/10 rounded-full blur-2xl group-hover:scale-125 group-hover:opacity-100 opacity-80 transition-all duration-500"></div>
            <div class="flex items-center gap-4 relative">
                <div class="p-3.5 bg-gradient-to-br from-skyAccent to-blue-500 text-white rounded-2xl shadow-sm shadow-sky-500/20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <div>
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Team</span>
                    <span class="text-3xl font-extrabold text-slate-900 dark:text-white mt-0.5">{{ $totalTeam }}</span>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-400 font-semibold relative">
                <span>Supervised Reports</span>
                <span class="text-skyAccent font-bold">Active Workspace</span>
            </div>
        </div>

        <!-- Active Today Card -->
        <div class="group bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-emerald-500/40 dark:hover:border-emerald-500/40 p-6 rounded-2xl shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between relative overflow-hidden">
            <div class="absolute -top-6 -right-6 w-36 h-36 bg-gradient-to-br from-emerald-500/10 to-teal-550/10 rounded-full blur-2xl group-hover:scale-125 group-hover:opacity-100 opacity-80 transition-all duration-500"></div>
            <div class="flex items-center gap-4 relative">
                <div class="p-3.5 bg-gradient-to-br from-emerald-400 to-teal-500 text-white rounded-2xl shadow-sm shadow-emerald-500/20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest">Active Today</span>
                    <span class="text-3xl font-extrabold text-slate-900 dark:text-white mt-0.5">{{ $activeToday }}/{{ $totalTeam }}</span>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-400 font-semibold relative">
                <span>Attendance Ratio</span>
                <span class="text-emerald-500 font-bold">
                    @if($totalTeam > 0)
                        {{ round(($activeToday / $totalTeam) * 100) }}% Present
                    @else
                        0% Present
                    @endif
                </span>
            </div>
        </div>

        <!-- Manager Score Card -->
        <div class="group bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-violet-500/40 dark:hover:border-violet-500/40 p-6 rounded-2xl shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between relative overflow-hidden">
            <div class="absolute -top-6 -right-6 w-36 h-36 bg-gradient-to-br from-violet-500/10 to-indigo-550/10 rounded-full blur-2xl group-hover:scale-125 group-hover:opacity-100 opacity-80 transition-all duration-500"></div>
            <div class="flex items-center gap-4 relative">
                <div class="p-3.5 bg-gradient-to-br from-violet-400 to-indigo-500 text-white rounded-2xl shadow-sm shadow-violet-500/20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2m0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                </div>
                <div>
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest">Manager Score</span>
                    <span class="text-3xl font-extrabold text-skyAccent dark:text-blue-400 mt-0.5">{{ $metrics['manager_score'] }}%</span>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-450 font-semibold relative">
                <span>Weighted Performance</span>
                <span class="text-violet-500 font-bold">Grade Target</span>
            </div>
        </div>

        <!-- Team Health Card -->
        <div class="group bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-amber-500/40 dark:hover:border-amber-500/40 p-6 rounded-2xl shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between relative overflow-hidden">
            <div class="absolute -top-6 -right-6 w-36 h-36 bg-gradient-to-br from-amber-500/10 to-orange-550/10 rounded-full blur-2xl group-hover:scale-125 group-hover:opacity-100 opacity-80 transition-all duration-500"></div>
            <div class="flex items-center gap-4 relative">
                <div class="p-3.5 bg-gradient-to-br from-amber-400 to-orange-500 text-white rounded-2xl shadow-sm shadow-amber-500/20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <div>
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest">Team Status</span>
                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $healthClass }} mt-1.5">{{ $healthStatus }}</span>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-450 font-semibold relative">
                <span>Operational Health</span>
                <span class="text-amber-500 font-bold">Live Status</span>
            </div>
        </div>
    </div>

    <!-- Analytics Breakdown -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        <!-- Deterministic KPI progress bars -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm lg:col-span-2 space-y-6">
            <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3">Manager Performance KPIs</h3>
            
            <div class="space-y-5">
                <!-- Task Completion Rate (40% Weight) -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-slate-700 dark:text-slate-300">Team Task Completion Rate <span class="text-xs text-slate-400 dark:text-slate-500">(40% Weight)</span></span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $metrics['task_completion_rate'] }}%</span>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 h-2.5 rounded-full overflow-hidden">
                        <div class="bg-skyAccent dark:bg-blue-600 h-full rounded-full transition-all duration-500" style="width: {{ $metrics['task_completion_rate'] }}%"></div>
                    </div>
                    <div class="flex justify-between text-[11px] text-slate-400 dark:text-slate-500">
                        <span>{{ $metrics['metrics_breakdown']['completed_tasks'] }} completed</span>
                        <span>{{ $metrics['metrics_breakdown']['total_assigned_tasks'] }} assigned</span>
                    </div>
                </div>

                <!-- Deadline Adherence Rate (20% Weight) -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-slate-700 dark:text-slate-300">Deadline Adherence Rate <span class="text-xs text-slate-400 dark:text-slate-500">(20% Weight)</span></span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $metrics['deadline_adherence_rate'] }}%</span>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 h-2.5 rounded-full overflow-hidden">
                        <div class="bg-emerald-500 dark:bg-emerald-600 h-full rounded-full transition-all duration-500" style="width: {{ $metrics['deadline_adherence_rate'] }}%"></div>
                    </div>
                    <div class="flex justify-between text-[11px] text-slate-400 dark:text-slate-500">
                        <span>{{ $metrics['metrics_breakdown']['completed_on_time_tasks'] }} on-time</span>
                        <span>{{ $metrics['metrics_breakdown']['completed_tasks'] }} total completed</span>
                    </div>
                </div>

                <!-- Team Productivity Score (20% Weight) -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-slate-700 dark:text-slate-300">Team Productivity Score <span class="text-xs text-slate-400 dark:text-slate-505">(20% Weight)</span></span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $metrics['productivity_score'] }}%</span>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 h-2.5 rounded-full overflow-hidden">
                        <div class="bg-violet-500 dark:bg-violet-600 h-full rounded-full transition-all duration-500" style="width: {{ $metrics['productivity_score'] }}%"></div>
                    </div>
                    <div class="flex justify-between text-[11px] text-slate-400 dark:text-slate-505">
                        <span>{{ $metrics['metrics_breakdown']['total_hours_logged'] }} logged hrs</span>
                        <span>{{ $metrics['metrics_breakdown']['expected_hours'] }} expected hrs</span>
                    </div>
                </div>

                <!-- Team Consistency Score (20% Weight) -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-slate-700 dark:text-slate-300">Team Workload Consistency <span class="text-xs text-slate-400 dark:text-slate-505">(20% Weight)</span></span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $metrics['consistency_score'] }}%</span>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 h-2.5 rounded-full overflow-hidden">
                        <div class="bg-amber-500 dark:bg-amber-600 h-full rounded-full transition-all duration-500" style="width: {{ $metrics['consistency_score'] }}%"></div>
                    </div>
                    <div class="text-[11px] text-slate-450">
                        <span>Calculated based on daily team logged work hours variance.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Task Allocation Summary -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm flex flex-col justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3 mb-4">Task Allocations</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl">
                        <span class="text-sm font-semibold text-slate-600 dark:text-slate-300">Completed</span>
                        <span class="px-2.5 py-0.5 rounded-md bg-green-50 dark:bg-green-950/20 text-green-700 dark:text-green-400 text-xs font-bold">{{ $tasksCompleted }} Tasks</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl">
                        <span class="text-sm font-semibold text-slate-600 dark:text-slate-300">In Progress</span>
                        <span class="px-2.5 py-0.5 rounded-md bg-sky-50 dark:bg-sky-950/20 text-skyAccent dark:text-sky-400 text-xs font-bold">{{ $tasksInProgress }} Tasks</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl">
                        <span class="text-sm font-semibold text-slate-600 dark:text-slate-300">Pending</span>
                        <span class="px-2.5 py-0.5 rounded-md bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-400 text-xs font-bold">{{ $tasksPending }} Tasks</span>
                    </div>
                </div>
            </div>
            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 text-[11px] text-slate-400 dark:border-slate-800 text-[11px] text-slate-400 dark:text-slate-500">
                To assign tasks, go to the <a href="{{ route('dashboard.tasks.index') }}" class="underline text-skyAccent dark:text-blue-400 font-semibold">Task Board</a>.
            </div>
        </div>

        <!-- Attendance Breakdown Summary with Donut Chart -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 mb-4 gap-2">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Attendance</h3>
                    <form action="{{ route('dashboard.index') }}" method="GET" class="inline-block">
                        <input type="hidden" name="top" value="{{ $topLimit }}">
                        <select name="duration" onchange="this.form.submit()"
                                class="px-2 py-1 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 cursor-pointer">
                            <option value="today" {{ $duration === 'today' ? 'selected' : '' }}>Today</option>
                            <option value="7_days" {{ $duration === '7_days' ? 'selected' : '' }}>7 Days</option>
                            <option value="30_days" {{ $duration === '30_days' ? 'selected' : '' }}>30 Days</option>
                            <option value="this_month" {{ $duration === 'this_month' ? 'selected' : '' }}>Month</option>
                            <option value="all_time" {{ $duration === 'all_time' ? 'selected' : '' }}>All Time</option>
                        </select>
                    </form>
                </div>
                @if($presentCount + $lateCount + $absentCountRange > 0)
                    <div class="relative h-40 w-full flex items-center justify-center">
                        <canvas id="attendanceDonutChart"></canvas>
                    </div>
                @else
                    <div class="h-40 w-full flex items-center justify-center text-xs text-slate-400 italic">
                        No logs for this duration.
                    </div>
                @endif
            </div>
            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 text-[10px] text-slate-400 dark:text-slate-500 flex justify-between font-semibold">
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span> Present ({{ $presentCount }})</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span> Late ({{ $lateCount }})</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-rose-500 inline-block"></span> Absent ({{ $absentCountRange }})</span>
            </div>
        </div>
    </div>

    <!-- Top Performers Graphical Section -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 gap-2">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Top Performers Chart</h3>
            <form action="{{ route('dashboard.index') }}" method="GET" class="inline-block">
                <input type="hidden" name="duration" value="{{ $duration }}">
                <select name="top" onchange="this.form.submit()"
                        class="px-2 py-1 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 cursor-pointer">
                    <option value="3" {{ $topLimit == 3 ? 'selected' : '' }}>Top 3</option>
                    <option value="20" {{ $topLimit == 20 ? 'selected' : '' }}>Top 20</option>
                    <option value="50" {{ $topLimit == 50 ? 'selected' : '' }}>Top 50</option>
                </select>
            </form>
        </div>
        
        @if($performers->isNotEmpty())
            <div class="relative w-full overflow-x-auto pb-4 scrollbar-thin">
                <div id="topPerformersChartContainer" class="relative h-80">
                    <canvas id="topPerformersChart"></canvas>
                </div>
            </div>
        @else
            <div class="text-center py-12 text-sm text-slate-500 dark:text-slate-400">
                No active performance metrics yet.
            </div>
        @endif
    </div>

    <!-- Meeting Intelligence & Notes Overview -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Widget 1: Upcoming Meetings -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm space-y-4 flex flex-col justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3 mb-4 flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-skyAccent dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        Upcoming Meetings
                    </span>
                    <span class="px-2 py-0.5 rounded bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-bold">{{ $upcomingMeetings->count() }}</span>
                </h3>
                
                <div class="space-y-3 max-h-64 overflow-y-auto pr-1">
                    @forelse($upcomingMeetings as $meeting)
                        <div class="p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-slate-800/50 space-y-2">
                            <div class="flex items-start justify-between">
                                <div>
                                    <span class="block text-xs font-bold text-slate-800 dark:text-slate-200 truncate max-w-[160px]">{{ $meeting->title }}</span>
                                    <span class="block text-[10px] text-slate-400">Team: {{ $meeting->team->name }}</span>
                                </div>
                                <span class="inline-flex px-1.5 py-0.2 rounded text-[9px] font-extrabold border bg-blue-50 text-blue-700 border-blue-105 dark:bg-blue-950/20 dark:text-blue-400 dark:border-blue-900/30">
                                    {{ $meeting->status }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-[10px] text-slate-500 font-medium">
                                <span>{{ $meeting->meeting_date ? \Illuminate\Support\Carbon::parse($meeting->meeting_date)->format('M d, Y') : 'TBD' }} @ {{ $meeting->meeting_time ? \Illuminate\Support\Carbon::parse($meeting->meeting_time)->format('h:i A') : 'TBD' }}</span>
                            </div>
                            <div class="flex items-center gap-2 pt-1 border-t border-slate-100 dark:border-slate-850/50">
                                <a href="{{ route('dashboard.meetings.show', $meeting->id) }}" class="text-[10px] font-bold text-skyAccent hover:text-sky-600 dark:text-blue-400 dark:hover:text-blue-300">View Details</a>
                                @if($meeting->meeting_link)
                                    <span class="text-slate-300">|</span>
                                    <a href="{{ $meeting->meeting_link }}" target="_blank" class="text-[10px] font-bold text-emerald-600 hover:text-emerald-700 dark:text-green-400 dark:hover:text-green-300">Join</a>
                                @endif
                                <span class="text-slate-300">|</span>
                                <a href="{{ route('dashboard.teams.show', $meeting->team_id) }}?tab=meetings" class="text-[10px] font-bold text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-350">Reschedule</a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 text-xs text-slate-400 italic">No upcoming meetings scheduled.</div>
                    @endforelse
                </div>
            </div>
            @if($upcomingMeetings->isNotEmpty())
                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 text-[11px] text-slate-400 mt-2">
                    To schedule a meeting, visit a <a href="{{ route('dashboard.teams.index') }}" class="underline text-skyAccent dark:text-blue-400 font-semibold">Team Dashboard</a>.
                </div>
            @endif
        </div>

        <!-- Widget 2: Pending Action Items -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm space-y-4">
            <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3 mb-4 flex items-center justify-between">
                <span class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    Pending Action Items
                </span>
                <span class="px-2 py-0.5 rounded bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-bold">{{ $pendingActionItems->count() }}</span>
            </h3>
            
            <div class="space-y-3 max-h-64 overflow-y-auto pr-1">
                @forelse($pendingActionItems as $item)
                    <div class="p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-slate-800/50 space-y-1.5">
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-xs font-semibold text-slate-850 dark:text-slate-200 line-clamp-2 leading-snug">{{ $item->action_item }}</span>
                            <span class="inline-flex px-1.5 py-0.2 rounded text-[8px] font-extrabold uppercase border 
                                @if($item->priority === 'High') bg-red-50 text-red-700 border-red-100 dark:bg-red-950/20 dark:text-red-400 dark:border-red-900/30
                                @elseif($item->priority === 'Medium') bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900/30
                                @else bg-green-50 text-green-700 border-green-100 dark:bg-green-950/20 dark:text-green-400 dark:border-green-900/30
                                @endif">
                                {{ $item->priority }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-[10px] text-slate-400 pt-1 border-t border-slate-100 dark:border-slate-850/50">
                            <span>Assignee: <strong class="text-slate-700 dark:text-slate-300">{{ $item->assignee->name ?? 'Unassigned' }}</strong></span>
                            <span>Due: <strong class="text-red-500">{{ $item->due_date ? $item->due_date->format('M d') : 'No date' }}</strong></span>
                        </div>
                        <div class="text-[9px] text-slate-450 italic truncate">
                            Meeting: <a href="{{ route('dashboard.meetings.show', $item->meeting_id) }}" class="underline text-skyAccent dark:text-blue-400 font-semibold">{{ $item->meeting->title }}</a>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-6 text-xs text-slate-400 italic">All action items completed.</div>
                @endforelse
            </div>
        </div>

        <!-- Widget 3: Recent Meeting Summaries -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm space-y-4">
            <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3 mb-4 flex items-center justify-between">
                <span class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 4a2 2 0 00-2-2v3m2-3V9m0 0a2 2 0 012 2v8a2 2 0 01-2 2h-3"></path></svg>
                    Recent Notes & Summaries
                </span>
                <span class="px-2 py-0.5 rounded bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-bold">{{ $recentMeetingSummaries->count() }}</span>
            </h3>
            
            <div class="space-y-3 max-h-64 overflow-y-auto pr-1">
                @forelse($recentMeetingSummaries as $meeting)
                    <div class="p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-slate-800/50 space-y-1.5">
                        <div class="flex justify-between items-start">
                            <a href="{{ route('dashboard.meetings.show', $meeting->id) }}" class="text-xs font-bold text-slate-800 hover:text-skyAccent dark:text-slate-200 dark:hover:text-blue-400 transition-colors truncate max-w-[170px]">
                                {{ $meeting->title }}
                            </a>
                            <span class="text-[9px] text-slate-400 font-medium">{{ $meeting->meeting_date ? \Illuminate\Support\Carbon::parse($meeting->meeting_date)->format('M d') : '' }}</span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2 leading-relaxed">
                            {{ strip_tags($meeting->transcript->summary) }}
                        </p>
                        <div class="flex items-center justify-between text-[10px] pt-1 border-t border-slate-100 dark:border-slate-850/50 text-slate-450">
                            <span>Sentiment: <strong class="text-emerald-555 dark:text-green-400 font-bold">{{ $meeting->transcript->sentiment ?? 'Positive' }}</strong></span>
                            <a href="{{ route('dashboard.meetings.show', $meeting->id) }}" class="text-skyAccent dark:text-blue-400 font-bold hover:underline">View Transcript</a>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-6 text-xs text-slate-400 italic">No recent meeting notes synced.</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Actions Required / Attention Center -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm space-y-6">
        <div class="border-b border-slate-100 dark:border-slate-800 pb-3 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Attention Center: Actions Required
            </h3>
            <span class="px-2.5 py-0.5 rounded-full bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-400 text-xs font-semibold">
                {{ $absentCount + $overdueCount }} Issues Identified
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Column 1: Absent Employees -->
            <div class="space-y-4">
                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Absent Today</h4>
                <div class="space-y-3">
                    @forelse ($absentEmployees as $employee)
                        <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-slate-850">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-red-50 dark:bg-red-950/20 text-red-500 flex items-center justify-center font-bold text-xs">
                                    {{ substr($employee->name, 0, 2) }}
                                </div>
                                <div>
                                    <span class="block text-sm font-bold text-slate-800 dark:text-slate-200">{{ $employee->name }}</span>
                                    <span class="text-[10px] text-red-550 font-semibold bg-red-50 dark:bg-red-950/10 px-1.5 py-0.2 rounded border border-red-100 dark:border-red-900/30">Not Clocked In</span>
                                </div>
                            </div>
                            <form action="{{ route('dashboard.employees.send-reminder', $employee->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white shadow-sm hover:shadow transition-all">
                                    Remind Employee
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="p-4 rounded-xl border border-dashed border-slate-200 dark:border-slate-800 text-center text-xs text-slate-400">
                            Great! All team members checked in today.
                        </div>
                    @endforelse

                    @if($absentCount > 5)
                        <div class="p-2.5 text-center text-xs text-slate-500 bg-slate-50 dark:bg-slate-800/30 rounded-xl border border-slate-100 dark:border-slate-850">
                            Showing first 5 of {{ $absentCount }} absent employees.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Column 2: Overdue Tasks -->
            <div class="space-y-4">
                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Missed Deadlines / Overdue Tasks</h4>
                <div class="space-y-3">
                    @forelse ($overdueTasks as $task)
                        <div class="p-4 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-slate-850 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="space-y-1">
                                <span class="block text-sm font-bold text-slate-800 dark:text-slate-200">{{ $task->title }}</span>
                                <div class="flex items-center gap-2 text-[11px] text-slate-500">
                                    <span>Assignee: <strong class="text-slate-700 dark:text-slate-350">{{ $task->assignee->name ?? 'Unassigned' }}</strong></span>
                                    <span class="w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                    <span class="text-red-500 font-bold">Due: {{ $task->deadline ? $task->deadline->format('M d') : 'Overdue' }}</span>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2 shrink-0">
                                <form action="{{ route('dashboard.tasks.extend-deadline', $task->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-white border border-slate-250 hover:bg-slate-50 text-slate-700 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-300 dark:hover:bg-slate-800 transition-colors">
                                        Extend +2D
                                    </button>
                                </form>
                                @if($task->assigned_to)
                                    <a href="{{ route('dashboard.employees.show', $task->assigned_to) }}" class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-sky-50 hover:bg-sky-100 text-skyAccent dark:bg-blue-950/20 dark:hover:bg-blue-900/30 dark:text-blue-400 transition-colors">
                                        View
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-4 rounded-xl border border-dashed border-slate-200 dark:border-slate-800 text-center text-xs text-slate-400">
                            Excellent! No overdue tasks in your queue.
                        </div>
                    @endforelse

                    @if($overdueCount > 5)
                        <div class="p-2.5 text-center text-xs text-slate-500 bg-slate-50 dark:bg-slate-800/30 rounded-xl border border-slate-100 dark:border-slate-850">
                            Showing first 5 of {{ $overdueCount }} overdue tasks.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Load Chart.js and build charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Top Performers Bar Chart
        const ctxElement = document.getElementById('topPerformersChart');
        if (ctxElement) {
            const performersCount = {{ $performers->count() }};
            const chartContainer = document.getElementById('topPerformersChartContainer');
            if (chartContainer) {
                // Each performer gets 85px of horizontal space for their grouped bars + label
                const dynamicWidth = Math.max(chartContainer.parentElement.offsetWidth, performersCount * 85);
                chartContainer.style.width = dynamicWidth + 'px';
            }

            const ctx = ctxElement.getContext('2d');
            
            // Completed tasks gradient (Green/Emerald)
            const completedGradient = ctx.createLinearGradient(0, 300, 0, 0);
            completedGradient.addColorStop(0, 'rgba(16, 185, 129, 0.15)');
            completedGradient.addColorStop(1, 'rgba(16, 185, 129, 0.85)');

            // Assigned tasks gradient (Indigo/Purple)
            const assignedGradient = ctx.createLinearGradient(0, 300, 0, 0);
            assignedGradient.addColorStop(0, 'rgba(99, 102, 241, 0.15)');
            assignedGradient.addColorStop(1, 'rgba(99, 102, 241, 0.85)');

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($performers->pluck('name')) !!},
                    datasets: [
                        {
                            label: 'Tasks Completed',
                            data: {!! json_encode($performers->pluck('tasks_count')) !!},
                            backgroundColor: completedGradient,
                            borderColor: '#10b981',
                            borderWidth: 2,
                            borderRadius: 4,
                            maxBarThickness: 15
                        },
                        {
                            label: 'Tasks Assigned',
                            data: {!! json_encode($performers->pluck('tasks_assigned_count')) !!},
                            backgroundColor: assignedGradient,
                            borderColor: '#6366f1',
                            borderWidth: 2,
                            borderRadius: 4,
                            maxBarThickness: 15
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { 
                            display: true,
                            position: 'top',
                            labels: {
                                color: '#64748b',
                                font: { family: 'Plus Jakarta Sans', weight: 600 }
                            }
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleFont: { size: 13, weight: 'bold' },
                            bodyFont: { size: 12 },
                            padding: 12,
                            cornerRadius: 12,
                            displayColors: false
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: {
                                color: '#64748b',
                                font: { family: 'Plus Jakarta Sans', weight: 600 }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(148, 163, 184, 0.1)',
                                drawBorder: false
                            },
                            ticks: {
                                color: '#64748b',
                                font: { family: 'Plus Jakarta Sans', weight: 500 },
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        // Attendance Breakdown Donut Chart
        const attendanceCtx = document.getElementById('attendanceDonutChart');
        if (attendanceCtx) {
            new Chart(attendanceCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Present', 'Late', 'Absent'],
                    datasets: [{
                        data: [
                            {{ $presentCount }},
                            {{ $lateCount }},
                            {{ $absentCountRange }}
                        ],
                        backgroundColor: [
                            'rgba(16, 185, 129, 0.8)', // Emerald
                            'rgba(245, 158, 11, 0.8)', // Amber
                            'rgba(244, 63, 94, 0.8)'   // Rose
                        ],
                        borderColor: [
                            '#10b981',
                            '#f59e0b',
                            '#f43f5e'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleFont: { size: 12, weight: 'bold' },
                            bodyFont: { size: 11 },
                            padding: 10,
                            cornerRadius: 10,
                            displayColors: false
                        }
                    }
                }
            });
        }
    });
</script>
@endsection
