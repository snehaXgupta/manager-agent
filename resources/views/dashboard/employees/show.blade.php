@extends('layouts.app')

@section('content')
<div class="space-y-8 animate-fade-in" x-data="{ profileTab: 'tasks' }">
    <!-- Header/Back Nav -->
    <div class="space-y-4">
        <a href="{{ route('dashboard.employees.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-skyAccent dark:text-slate-400 dark:hover:text-blue-400 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to Team
        </a>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-sky-50 dark:bg-blue-950/20 text-skyAccent dark:text-blue-400 flex items-center justify-center font-bold text-lg border border-slate-200 dark:border-slate-800">
                    {{ substr($employee->name, 0, 2) }}
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $employee->name }}</h2>
                    <span class="text-xs text-slate-550 dark:text-slate-405 font-semibold">
                        {{ $employee->designation?->name ?? 'Employee' }} &bull; {{ $employee->department?->name ?? 'No Department Assigned' }}
                    </span>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center gap-4 text-right">
                <div class="text-sm text-slate-500 dark:text-slate-400 font-medium">
                    Email: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $employee->email }}</span>
                </div>
                
                <div class="flex items-center gap-3">
                    @if(!$todayAttendance)
                        <form action="{{ route('dashboard.employees.clock-in', $employee->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold rounded-xl shadow-sm transition-all flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h3a3 3 0 013 3v1" /></svg>
                                Clock In Employee
                            </button>
                        </form>
                    @elseif(!$todayAttendance->check_out)
                        <form action="{{ route('dashboard.employees.clock-out', $employee->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl shadow-sm transition-all flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h3a3 3 0 013 3v1" /></svg>
                                Clock Out Employee
                            </button>
                        </form>
                    @else
                        <span class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-xs font-bold rounded-xl border border-slate-200/60 dark:border-slate-800 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Clocked Out Today ({{ Carbon\Carbon::parse($todayAttendance->check_out)->format('h:i A') }})
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- GitLab Mapping Form -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-sky-50 dark:bg-blue-950/20 text-skyAccent dark:text-blue-400 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" /></svg>
                </div>
                <div>
                    <span class="block text-sm font-bold text-slate-800 dark:text-slate-200">GitLab Account Mapping</span>
                    @if($employee->gitlab_username)
                        <span class="block text-xs text-slate-400">Mapped to: <strong>{{ $employee->gitlab_username }}</strong> (ID: {{ $employee->gitlab_user_id }})</span>
                    @else
                        <span class="block text-xs text-red-500 font-medium">No GitLab account mapped yet. Map one below to fetch telemetry.</span>
                    @endif
                </div>
            </div>
            <form action="{{ route('dashboard.employees.map-gitlab', $employee->id) }}" method="POST" class="flex items-center gap-2 w-full sm:w-auto">
                @csrf
                <input type="text" name="gitlab_username_or_email" required placeholder="GitLab username or email..."
                       class="w-full sm:w-48 px-3 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs outline-none focus:ring-1 focus:ring-skyAccent text-slate-900 dark:text-white">
                <button type="submit" class="px-4 py-2 bg-skyAccent hover:bg-sky-600 text-white font-bold rounded-xl text-xs shrink-0 shadow-sm transition-colors">
                    {{ $employee->gitlab_username ? 'Remap Account' : 'Map Account' }}
                </button>
            </form>
        </div>
    </div>

    <!-- Developer Score Card -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 shadow-sm space-y-6">
        <div class="flex flex-col lg:flex-row items-center gap-8">
            <!-- Left: Visual Score Circle -->
            <div class="flex flex-col items-center text-center space-y-4 shrink-0 pb-6 lg:pb-0 lg:pr-8 lg:border-r border-slate-200 dark:border-slate-800 w-full lg:w-auto">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Weekly Developer Score</span>
                
                <div class="relative flex items-center justify-center">
                    <!-- Outer glowing ring -->
                    <div class="absolute inset-0 rounded-full bg-gradient-to-tr from-skyAccent via-indigo-500 to-purple-600 blur-md opacity-25 dark:opacity-45 animate-pulse"></div>
                    <!-- Circular Score Container -->
                    <div class="relative w-40 h-40 rounded-full border-4 border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 flex flex-col items-center justify-center shadow-inner">
                        <span class="text-4xl font-black bg-gradient-to-r from-skyAccent via-indigo-500 to-purple-600 bg-clip-text text-transparent">
                            {{ $weeklyMetrics['developer_score'] }}%
                        </span>
                        
                        @php
                            $score = $weeklyMetrics['developer_score'];
                            if ($score >= 85) {
                                $status = 'Excellent';
                                $badgeColor = 'bg-green-500/10 text-green-500 border-green-500/20';
                            } elseif ($score >= 65) {
                                $status = 'Healthy';
                                $badgeColor = 'bg-skyAccent/10 text-skyAccent border-skyAccent/20';
                            } else {
                                $status = 'Needs Attention';
                                $badgeColor = 'bg-rose-500/10 text-rose-500 border-rose-500/20';
                            }
                        @endphp
                        
                        <span class="mt-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeColor }} uppercase tracking-wider">
                            {{ $status }}
                        </span>
                    </div>
                </div>
                
                <div class="text-xs font-medium text-slate-500 dark:text-slate-400">
                    Monthly Avg: <span class="font-bold text-slate-800 dark:text-slate-200">{{ $monthlyMetrics['developer_score'] }}%</span>
                </div>
            </div>

            <!-- Right: Breakdown Components -->
            <div class="flex-1 w-full space-y-5">
                <div>
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Developer Score Evaluation Breakdown</h4>
                    <p class="text-xs text-slate-555 dark:text-slate-400">Calculated based on standard formula integrating tasks delivery, productivity hours, code quality, and peer review contributions.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Task Completion (40%) -->
                    <div class="space-y-2">
                        <div class="flex justify-between items-baseline text-xs">
                            <span class="font-bold text-slate-700 dark:text-slate-300">Task Completion <span class="text-slate-400 font-normal">(40% weight)</span></span>
                            <span class="font-extrabold text-slate-900 dark:text-white">{{ $weeklyMetrics['task_completion_rate'] }}%</span>
                        </div>
                        <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-skyAccent to-blue-500 rounded-full" style="width: {{ $weeklyMetrics['task_completion_rate'] }}%"></div>
                        </div>
                        <div class="flex justify-between text-[10px] font-medium text-slate-400">
                            <span>Contribution: {{ round($weeklyMetrics['task_completion_rate'] * 0.40, 1) }} / 40.0 pts</span>
                        </div>
                    </div>

                    <!-- Delivery Speed (20%) -->
                    <div class="space-y-2">
                        <div class="flex justify-between items-baseline text-xs">
                            <span class="font-bold text-slate-700 dark:text-slate-300">Delivery Speed <span class="text-slate-400 font-normal">(20% weight)</span></span>
                            <span class="font-extrabold text-slate-900 dark:text-white">{{ $weeklyMetrics['delivery_speed_score'] }}%</span>
                        </div>
                        <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-teal-400 to-skyAccent rounded-full" style="width: {{ $weeklyMetrics['delivery_speed_score'] }}%"></div>
                        </div>
                        <div class="flex justify-between text-[10px] font-medium text-slate-400">
                            <span>Contribution: {{ round($weeklyMetrics['delivery_speed_score'] * 0.20, 1) }} / 20.0 pts</span>
                        </div>
                    </div>

                    <!-- Code Quality (20%) -->
                    <div class="space-y-2">
                        <div class="flex justify-between items-baseline text-xs">
                            <span class="font-bold text-slate-700 dark:text-slate-300">Code Quality <span class="text-slate-400 font-normal">(20% weight)</span></span>
                            <span class="font-extrabold text-slate-900 dark:text-white">{{ $weeklyMetrics['code_quality_score'] }}%</span>
                        </div>
                        <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-purple-500 to-indigo-500 rounded-full" style="width: {{ $weeklyMetrics['code_quality_score'] }}%"></div>
                        </div>
                        <div class="flex justify-between text-[10px] font-medium text-slate-400">
                            <span>Contribution: {{ round($weeklyMetrics['code_quality_score'] * 0.20, 1) }} / 20.0 pts</span>
                        </div>
                    </div>

                    <!-- Reviews (20%) -->
                    <div class="space-y-2">
                        <div class="flex justify-between items-baseline text-xs">
                            <span class="font-bold text-slate-700 dark:text-slate-300">Peer Reviews <span class="text-slate-400 font-normal">(20% weight)</span></span>
                            <span class="font-extrabold text-slate-900 dark:text-white">{{ $weeklyMetrics['reviews_score'] }}%</span>
                        </div>
                        <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-pink-500 to-purple-500 rounded-full" style="width: {{ $weeklyMetrics['reviews_score'] }}%"></div>
                        </div>
                        <div class="flex justify-between text-[10px] font-medium text-slate-400">
                            <span>Contribution: {{ round($weeklyMetrics['reviews_score'] * 0.20, 1) }} / 20.0 pts</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Performance Analysis & Insights -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 shadow-sm space-y-6">
        <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
            <div class="p-2 bg-purple-50 dark:bg-purple-950/20 text-purple-500 rounded-xl">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">AI Performance Analysis</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Qualitative developer assessment and predictive feedback from manager agent.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Executive Summary & Rating -->
            <div class="lg:col-span-1 space-y-4 bg-slate-50 dark:bg-slate-800/20 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/40">
                <div class="space-y-1">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block">AI Performance Rating</span>
                    <span class="inline-flex px-3 py-1 rounded-full text-xs font-extrabold tracking-wide uppercase border 
                        @if(($insights['performance_rating'] ?? '') === 'Outstanding')
                            bg-green-50 text-green-700 border-green-200 dark:bg-green-950/25 dark:text-green-400 dark:border-green-900/30
                        @elseif(($insights['performance_rating'] ?? '') === 'Proficient')
                            bg-sky-50 text-skyAccent border-sky-200 dark:bg-blue-950/25 dark:text-blue-400 dark:border-blue-900/30
                        @else
                            bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/25 dark:text-rose-400 dark:border-rose-900/30
                        @endif">
                        {{ $insights['performance_rating'] ?? 'Unrated' }}
                    </span>
                </div>
                
                <div class="space-y-1">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block">Summary</span>
                    <p class="text-xs text-slate-650 dark:text-slate-300 leading-relaxed font-medium">
                        {{ $insights['summary'] ?? 'No analysis available.' }}
                    </p>
                </div>
            </div>

            <!-- Right: Detailed Strengths, Weaknesses, Risks -->
            <div class="lg:col-span-2 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Strengths -->
                    <div class="space-y-3">
                        <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Observed Strengths
                        </span>
                        <ul class="space-y-2">
                            @forelse($insights['strengths'] ?? [] as $strength)
                                <li class="text-xs text-slate-650 dark:text-slate-350 flex items-start gap-2">
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
                            Areas to Improve
                        </span>
                        <ul class="space-y-2">
                            @forelse($insights['weaknesses'] ?? [] as $weakness)
                                <li class="text-xs text-slate-650 dark:text-slate-350 flex items-start gap-2">
                                    <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    <span>{{ $weakness }}</span>
                                </li>
                            @empty
                                <li class="text-xs text-slate-400 italic">No developmental areas recorded.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Risks -->
                    <div class="space-y-3">
                        <span class="text-xs font-bold text-red-600 dark:text-red-400 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                            Burnout & Delivery Risks
                        </span>
                        <ul class="space-y-2">
                            @forelse($insights['risks'] ?? [] as $risk)
                                <li class="text-xs text-slate-650 dark:text-slate-350 flex items-start gap-2">
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
                            Manager Action Plan
                        </span>
                        <ul class="space-y-2">
                            @forelse($insights['recommendations'] ?? [] as $rec)
                                <li class="text-xs text-slate-650 dark:text-slate-350 flex items-start gap-2">
                                    <svg class="w-4 h-4 text-indigo-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364.364l-.707.707M21 12h-1M4 9H3m15.364 6.364l-.707-.707M6.343 6.343l.707-.707m9.9 5.05a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                    <span>{{ $rec }}</span>
                                </li>
                            @empty
                                <li class="text-xs text-slate-400 italic">No actionable suggestions.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TABS CONTAINER -->
    <div class="space-y-6">
        <!-- Tabs Header Buttons -->
        <div class="border-b border-slate-200 dark:border-slate-800">
            <nav class="flex flex-wrap -mb-px space-x-6" aria-label="Profile Tabs">
                <button @click="profileTab = 'tasks'" 
                        :class="profileTab === 'tasks' ? 'border-skyAccent text-skyAccent dark:border-blue-450 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'"
                        class="py-4 px-1 border-b-2 font-bold text-xs uppercase tracking-wider focus:outline-none transition-all flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    Tasks Board
                </button>
                <button @click="profileTab = 'skills'" 
                        :class="profileTab === 'skills' ? 'border-skyAccent text-skyAccent dark:border-blue-450 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'"
                        class="py-4 px-1 border-b-2 font-bold text-xs uppercase tracking-wider focus:outline-none transition-all flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364.364l-.707.707M21 12h-1M4 9H3m15.364 6.364l-.707-.707M6.343 6.343l.707-.707m9.9 5.05a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Skill Matrix
                </button>
                <button @click="profileTab = 'performance'" 
                        :class="profileTab === 'performance' ? 'border-skyAccent text-skyAccent dark:border-blue-450 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'"
                        class="py-4 px-1 border-b-2 font-bold text-xs uppercase tracking-wider focus:outline-none transition-all flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    Performance History
                </button>
                <button @click="profileTab = 'projects'" 
                        :class="profileTab === 'projects' ? 'border-skyAccent text-skyAccent dark:border-blue-450 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'"
                        class="py-4 px-1 border-b-2 font-bold text-xs uppercase tracking-wider focus:outline-none transition-all flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
                    Project Allocations
                </button>
                @if($employee->gitlab_username)
                    <button @click="profileTab = 'gitlab'" 
                            :class="profileTab === 'gitlab' ? 'border-skyAccent text-skyAccent dark:border-blue-450 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'"
                            class="py-4 px-1 border-b-2 font-bold text-xs uppercase tracking-wider focus:outline-none transition-all flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" /></svg>
                        GitLab Telemetry
                    </button>
                @endif
            </nav>
        </div>

        <!-- TAB CONTENT: TASKS BOARD -->
        <div x-show="profileTab === 'tasks'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/40 text-slate-500 dark:text-slate-400 text-xs font-bold uppercase border-b border-slate-200 dark:border-slate-800">
                            <th class="px-6 py-4">Task Name</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Deadline</th>
                            <th class="px-6 py-4">Status Indicator</th>
                            <th class="px-6 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                        @forelse ($tasks as $task)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="block font-semibold text-slate-900 dark:text-white">{{ $task->title }}</span>
                                    <span class="block text-xs text-slate-400 dark:text-slate-500 max-w-sm truncate">{{ $task->description ?? 'No description.' }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($task->status === 'completed')
                                        <span class="inline-flex px-2 py-0.5 rounded bg-green-50 border border-green-200 text-green-700 dark:bg-green-950/20 dark:border-green-800 dark:text-green-400 text-xs font-semibold">Completed</span>
                                    @elseif ($task->status === 'in_progress')
                                        <span class="inline-flex px-2 py-0.5 rounded bg-sky-50 border border-sky-200 text-skyAccent dark:bg-sky-950/20 dark:border-sky-850 dark:text-sky-400 text-xs font-semibold">In Progress</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded bg-slate-100 border border-slate-200 text-slate-600 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400 text-xs font-semibold">Pending</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-slate-655 dark:text-slate-350 font-medium">
                                    {{ $task->deadline ? $task->deadline->format('M d, Y') : 'No Deadline' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($task->status === 'completed')
                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 text-[10px] font-bold">On-time</span>
                                    @elseif ($task->deadline)
                                        @if ($task->deadline->isPast())
                                            <span class="inline-flex px-2 py-0.5 rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 text-[10px] font-bold">Overdue</span>
                                        @elseif ($task->deadline->diffInHours(now()) <= 48)
                                            <span class="inline-flex px-2 py-0.5 rounded-full bg-orange-100 text-orange-850 dark:bg-orange-900/30 dark:text-orange-400 text-[10px] font-bold">Urgent (<48h)</span>
                                        @else
                                            <span class="inline-flex px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 text-[10px] font-bold">Normal</span>
                                        @endif
                                    @else
                                        <span class="text-slate-400 text-xs">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-bold">
                                    @if ($task->status !== 'completed')
                                        <form action="{{ route('dashboard.tasks.update', $task->id) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="completed">
                                            <button type="submit" class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300">
                                                Mark Complete
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-slate-400 font-medium">Archived</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-400 text-xs">No tasks assigned to this employee.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB CONTENT: SKILL MATRIX -->
        <div x-show="profileTab === 'skills'" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Skill Matrix Display -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm lg:col-span-2 space-y-4">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3">
                    Assigned Skills & Proficiencies
                </h3>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="text-slate-500 text-xs font-bold uppercase border-b border-slate-100 dark:border-slate-850">
                                <th class="py-3 px-2">Skill</th>
                                <th class="py-3 px-2">Proficiency</th>
                                <th class="py-3 px-2 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-850">
                            @forelse($employee->skills as $skill)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                                    <td class="py-4 px-2 font-bold text-slate-800 dark:text-slate-200">
                                        {{ $skill->name }}
                                    </td>
                                    <td class="py-4 px-2 whitespace-nowrap">
                                        <!-- Star System (1-5 Proficiencies) -->
                                        <div class="flex items-center gap-1 text-amber-400">
                                            @for($s = 1; $s <= 5; $s++)
                                                <svg class="w-4 h-4 {{ $s <= $skill->pivot->proficiency ? 'fill-current' : 'text-slate-200 dark:text-slate-800' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.907c.969 0 1.371 1.24.588 1.81l-3.97 2.883a1 1 0 00-.364 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.971-2.883a1 1 0 00-1.175 0l-3.97 2.883c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.364-1.118l-3.97-2.883c-.783-.57-.38-1.81.588-1.81h4.906a1 1 0 00.95-.69l1.519-4.674z"></path>
                                                </svg>
                                            @endfor
                                            <span class="ml-1 text-xs text-slate-500 font-bold">({{ $skill->pivot->proficiency }}/5)</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-2 text-right">
                                        <form action="{{ route('dashboard.employees.skills.remove', [$employee->id, $skill->id]) }}" method="POST" onsubmit="return confirm('Remove this skill from employee?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-750 transition-colors">
                                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-8 text-center text-slate-400 text-xs">No skills assigned yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add Skill Form -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm lg:col-span-1 h-fit space-y-4">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3">
                    Assign / Update Skill
                </h3>
                <form action="{{ route('dashboard.employees.skills.add', $employee->id) }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="space-y-1">
                        <label for="skill_id" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Select Skill</label>
                        <select name="skill_id" id="skill_id" required
                                class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-sm text-slate-900 dark:text-white">
                            <option value="">-- Choose Skill --</option>
                            @foreach($allSkills as $sk)
                                <option value="{{ $sk->id }}">{{ $sk->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label for="proficiency" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Proficiency (1 to 5 Stars)</label>
                        <select name="proficiency" id="proficiency" required
                                class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-sm text-slate-900 dark:text-white">
                            <option value="1">1 Star - Novice</option>
                            <option value="2">2 Stars - Advanced Beginner</option>
                            <option value="3" selected>3 Stars - Competent</option>
                            <option value="4">4 Stars - Proficient</option>
                            <option value="5">5 Stars - Expert</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full py-2.5 bg-skyAccent hover:bg-sky-600 text-white font-bold rounded-xl shadow text-xs">
                        Assign Skill
                    </button>
                </form>
            </div>
        </div>

        <!-- TAB CONTENT: PERFORMANCE HISTORY -->
        <div x-show="profileTab === 'performance'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 shadow-sm space-y-6">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3">
                Historical Weekly Performance Timeline
            </h3>

            <!-- Line Graph Container -->
            <div class="h-64 w-full relative">
                <canvas id="performanceHistoryChart"></canvas>
            </div>

            <!-- Historical Data Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr class="text-slate-500 text-xs font-bold uppercase border-b border-slate-100 dark:border-slate-850">
                            <th class="py-3 px-2">Period</th>
                            <th class="py-3 px-2 text-center">Developer Score</th>
                            <th class="py-3 px-2 text-center">Task Completion</th>
                            <th class="py-3 px-2 text-center">Deadline Adherence</th>
                            <th class="py-3 px-2 text-center">Productivity Score</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-850">
                        @foreach(array_reverse($performanceHistory) as $hist)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10 text-xs font-medium">
                                <td class="py-3 px-2 text-slate-800 dark:text-slate-250 font-bold">
                                    {{ $hist['label'] }}
                                </td>
                                <td class="py-3 px-2 text-center font-bold text-skyAccent">
                                    {{ $hist['developer_score'] }}%
                                </td>
                                <td class="py-3 px-2 text-center text-slate-600 dark:text-slate-350">
                                    {{ $hist['task_completion_rate'] }}%
                                </td>
                                <td class="py-3 px-2 text-center text-slate-600 dark:text-slate-350">
                                    {{ $hist['deadline_adherence_rate'] }}%
                                </td>
                                <td class="py-3 px-2 text-center text-slate-600 dark:text-slate-350">
                                    {{ $hist['productivity_score'] }}%
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB CONTENT: PROJECT ALLOCATIONS -->
        <div x-show="profileTab === 'projects'" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Projects allocated -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm lg:col-span-2 space-y-4">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3">
                    Active Project Mappings
                </h3>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="text-slate-500 text-xs font-bold uppercase border-b border-slate-100 dark:border-slate-850">
                                <th class="py-3 px-2">Project</th>
                                <th class="py-3 px-2">GitLab Member ID</th>
                                <th class="py-3 px-2 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-850">
                            @forelse($employee->projects as $project)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                                    <td class="py-4 px-2">
                                        <span class="block font-bold text-slate-800 dark:text-slate-200 text-xs">{{ $project->name }}</span>
                                        <span class="block text-[11px] text-slate-400 mt-0.5 truncate max-w-sm">{{ $project->description }}</span>
                                    </td>
                                    <td class="py-4 px-2 whitespace-nowrap text-xs font-semibold text-slate-600 dark:text-slate-350">
                                        {{ $project->pivot->gitlab_member_id ?: 'Not Mapped' }}
                                    </td>
                                    <td class="py-4 px-2 text-right">
                                        <form action="{{ route('dashboard.employees.projects.deallocate', [$employee->id, $project->id]) }}" method="POST" onsubmit="return confirm('Remove user from this project?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2 py-1 bg-red-50 hover:bg-red-100 dark:bg-red-950/20 text-red-655 dark:text-red-400 rounded-lg text-[10px] font-bold border border-red-200 dark:border-red-900/50">
                                                Deallocate
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-8 text-center text-slate-400 text-xs">No active project assignments.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Allocate Project Form -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm lg:col-span-1 h-fit space-y-4">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3">
                    Assign New Project
                </h3>
                <form action="{{ route('dashboard.employees.projects.allocate', $employee->id) }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="space-y-1">
                        <label for="project_id" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Select Project</label>
                        <select name="project_id" id="project_id" required
                                class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-sm text-slate-900 dark:text-white">
                            <option value="">-- Choose Project --</option>
                            @foreach($allProjects as $proj)
                                <option value="{{ $proj->id }}">{{ $proj->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label for="gitlab_member_id" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">GitLab Member ID (Optional)</label>
                        <input type="text" name="gitlab_member_id" id="gitlab_member_id" placeholder="e.g. 82381"
                               class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-skyAccent">
                    </div>
                    <button type="submit" class="w-full py-2.5 bg-skyAccent hover:bg-sky-600 text-white font-bold rounded-xl shadow text-xs">
                        Assign Project
                    </button>
                </form>
            </div>
        </div>

        <!-- TAB CONTENT: GITLAB TELEMETRY -->
        @if($employee->gitlab_username)
            <div x-show="profileTab === 'gitlab'" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Stats Card -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-6 lg:col-span-1">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3">
                        GitLab Stats Overview
                    </h3>

                    <div class="space-y-4 text-xs font-semibold text-slate-700 dark:text-slate-350">
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-medium">Projects Assigned:</span>
                            <span class="text-slate-900 dark:text-white">{{ $engineeringMetrics['projects_assigned'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-medium">Repository Access:</span>
                            <span class="text-slate-900 dark:text-white">{{ $engineeringMetrics['repos_accessed'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-medium">Total Commits:</span>
                            <span class="text-skyAccent">{{ $engineeringMetrics['commits_count'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-medium">Open MRs:</span>
                            <span class="text-indigo-500">{{ $engineeringMetrics['open_mrs'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-medium">Merged MRs:</span>
                            <span class="text-emerald-500">{{ $engineeringMetrics['merged_mrs'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-medium">Reviews Performed:</span>
                            <span class="text-amber-500">{{ $engineeringMetrics['reviews_count'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-medium">Approvals Given:</span>
                            <span class="text-violet-500">{{ $engineeringMetrics['approvals_count'] }}</span>
                        </div>
                    </div>
                </div>

                <!-- Activity Timeline -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-6 lg:col-span-2">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3">
                        GitLab Activity Timeline
                    </h3>

                    <div class="space-y-4 max-h-80 overflow-y-auto pr-1">
                        @forelse($engineeringTimeline as $act)
                            <div class="flex items-start gap-3 p-3 bg-slate-50 dark:bg-slate-800/20 rounded-2xl border border-slate-100 dark:border-slate-800">
                                <div class="p-1.5 rounded bg-sky-50 dark:bg-blue-950/20 text-skyAccent dark:text-blue-400 mt-0.5 animate-pulse-subtle">
                                    @if($act['type'] === 'commit')
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" /></svg>
                                    @else
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                                    @endif
                                </div>
                                <div class="text-xs flex-1">
                                    <div class="flex justify-between items-baseline">
                                        <span class="font-bold text-slate-850 dark:text-slate-200">{{ $act['title'] }}</span>
                                        <span class="text-[10px] text-slate-400 font-semibold shrink-0">{{ $act['time']->diffForHumans() }}</span>
                                    </div>
                                    <span class="block text-[10px] text-slate-400 font-mono mt-1">{{ $act['meta'] }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-6 text-xs text-slate-400 italic">
                                No GitLab activity timeline records synced.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Chart Script for Performance History -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('performanceHistoryChart');
        if (ctx) {
            const dataHistory = @json($performanceHistory);
            const labels = dataHistory.map(item => item.label);
            const scores = dataHistory.map(item => item.developer_score);
            const tasks = dataHistory.map(item => item.task_completion_rate);
            const adherence = dataHistory.map(item => item.deadline_adherence_rate);

            const isDark = document.documentElement.classList.contains('dark');
            const gridColor = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.05)';
            const textColor = isDark ? '#94a3b8' : '#64748b';

            new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Developer Score (%)',
                            data: scores,
                            borderColor: '#0ea5e9',
                            backgroundColor: 'rgba(14, 165, 233, 0.05)',
                            borderWidth: 3,
                            tension: 0.35,
                            fill: true,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        },
                        {
                            label: 'Task Completion (%)',
                            data: tasks,
                            borderColor: '#8b5cf6',
                            borderWidth: 1.5,
                            borderDash: [5, 5],
                            tension: 0.3,
                            fill: false,
                            pointRadius: 2
                        },
                        {
                            label: 'Adherence (%)',
                            data: adherence,
                            borderColor: '#10b981',
                            borderWidth: 1.5,
                            borderDash: [5, 5],
                            tension: 0.3,
                            fill: false,
                            pointRadius: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: textColor,
                                font: {
                                    family: '"Plus Jakarta Sans", sans-serif',
                                    size: 11
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            min: 0,
                            max: 100,
                            grid: {
                                color: gridColor
                            },
                            ticks: {
                                color: textColor,
                                font: {
                                    size: 10
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: textColor,
                                font: {
                                    size: 10
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endsection
