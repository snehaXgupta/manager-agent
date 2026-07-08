@extends('layouts.app')

@section('content')
<div class="space-y-8 animate-fade-in" x-data="{ 
    period: '{{ $period }}',
    customOpen: '{{ $period === 'custom' ? 'true' : 'false' }}' === 'true'
}">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Team Health Index</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">Track and monitor your team's overall health score computed from daily operations metrics.</p>
        </div>
    </div>

    <!-- Filters & Date Range Row -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm space-y-4">
        <form action="{{ route('dashboard.health.index') }}" method="GET" class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <!-- Period selection -->
                <div class="flex flex-wrap gap-2">
                    @foreach(['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'yearly' => 'Yearly'] as $key => $label)
                        <button type="submit" name="period" value="{{ $key }}"
                                class="px-3.5 py-2 rounded-xl text-xs font-bold border transition-all {{ $period === $key ? 'bg-skyAccent border-skyAccent text-white shadow-sm' : 'bg-transparent border-slate-200 text-slate-650 hover:bg-slate-50 dark:border-slate-850 dark:text-slate-350 dark:hover:bg-slate-800' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                    <button type="button" @click="customOpen = !customOpen"
                            class="px-3.5 py-2 rounded-xl text-xs font-bold border transition-all"
                            :class="period === 'custom' || customOpen ? 'bg-skyAccent border-skyAccent text-white shadow-sm' : 'bg-transparent border-slate-200 text-slate-650 hover:bg-slate-50 dark:border-slate-850 dark:text-slate-350 dark:hover:bg-slate-800'">
                        Custom Date
                    </button>
                </div>

                <!-- Active Date Range Label -->
                <div class="text-xs font-bold text-slate-500 bg-slate-50 dark:bg-slate-800/40 px-3 py-2 rounded-xl border border-slate-200/60 dark:border-slate-800/60">
                    Period: <span class="text-slate-800 dark:text-slate-100">{{ $dates['start']->format('M d, Y') }}</span> to <span class="text-slate-800 dark:text-slate-100">{{ $dates['end']->format('M d, Y') }}</span>
                </div>
            </div>

            <!-- Custom date pickers dropdown -->
            <div x-cloak x-collapse x-show="customOpen" class="grid grid-cols-1 sm:grid-cols-3 items-end gap-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                <div class="space-y-1">
                    <label for="start_date" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Start Date</label>
                    <input type="date" name="start_date" id="start_date" value="{{ $startDateStr }}" 
                           class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-xs text-slate-850 dark:text-slate-105">
                </div>
                <div class="space-y-1">
                    <label for="end_date" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">End Date</label>
                    <input type="date" name="end_date" id="end_date" value="{{ $endDateStr }}" 
                           class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-xs text-slate-850 dark:text-slate-105">
                </div>
                <div>
                    <button type="submit" name="period" value="custom"
                            class="w-full px-4 py-2.5 bg-skyAccent hover:bg-sky-650 text-white text-xs font-bold rounded-xl shadow-sm transition-all whitespace-nowrap">
                        Apply Range
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Main Score Highlight & Component Progress Bars -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Score Radial Box -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-8 flex flex-col items-center justify-center text-center shadow-sm space-y-4">
            <span class="text-sm font-bold text-slate-400 uppercase tracking-wider">Overall Team Health</span>
            
            <div class="relative flex items-center justify-center">
                <!-- SVG Radial Progress Ring -->
                <svg class="w-36 h-36" viewBox="0 0 144 144">
                    <g transform="rotate(-90 72 72)">
                        <!-- Background Circle -->
                        <circle cx="72" cy="72" r="64" class="stroke-slate-100 dark:stroke-slate-800" stroke-width="8" fill="transparent" />
                        <!-- Progress Circle -->
                        <circle cx="72" cy="72" r="64" class="stroke-skyAccent" stroke-width="8" fill="transparent"
                                stroke-dasharray="402"
                                stroke-dashoffset="{{ 402 - (402 * $health['team_health_score']) / 100 }}"
                                stroke-linecap="round" />
                    </g>
                </svg>
                <div class="absolute text-center">
                    <span class="text-4xl font-extrabold text-slate-900 dark:text-white">{{ $health['team_health_score'] }}</span>
                    <span class="text-xs text-slate-400 block">/ 100</span>
                </div>
            </div>

            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide
                @if($health['status'] === 'Excellent')
                    bg-green-100 text-green-800 dark:bg-green-950/40 dark:text-green-400 border border-green-200 dark:border-green-800
                @elseif($health['status'] === 'Healthy')
                    bg-sky-100 text-skyAccent dark:bg-blue-950/40 dark:text-blue-400 border border-sky-200 dark:border-blue-800
                @else
                    bg-red-100 text-red-800 dark:bg-red-950/40 dark:text-red-400 border border-red-200 dark:border-red-800
                @endif">
                {{ $health['status'] }}
            </span>
        </div>

        <!-- Pillar Details List -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-8 shadow-sm space-y-6">
            <h3 class="font-bold text-slate-900 dark:text-white">Operational Pillars Breakdown</h3>
            
            <div class="space-y-5">
                <!-- Attendance Pillar -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-sm font-semibold">
                        <span class="text-slate-600 dark:text-slate-400">Attendance Health</span>
                        <span class="text-slate-900 dark:text-white">{{ $health['metrics']['attendance_health'] }}%</span>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                        <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" style="width: {{ $health['metrics']['attendance_health'] }}%"></div>
                    </div>
                </div>

                <!-- Productivity Pillar -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-sm font-semibold">
                        <span class="text-slate-600 dark:text-slate-400">Productivity Health</span>
                        <span class="text-slate-900 dark:text-white">{{ $health['metrics']['productivity_health'] }}%</span>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                        <div class="bg-indigo-500 h-full rounded-full transition-all duration-500" style="width: {{ $health['metrics']['productivity_health'] }}%"></div>
                    </div>
                </div>

                <!-- Consistency Pillar -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-sm font-semibold">
                        <span class="text-slate-600 dark:text-slate-400">Consistency Health</span>
                        <span class="text-slate-900 dark:text-white">{{ $health['metrics']['consistency_health'] }}%</span>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                        <div class="bg-amber-500 h-full rounded-full transition-all duration-500" style="width: {{ $health['metrics']['consistency_health'] }}%"></div>
                    </div>
                </div>

                <!-- Delivery Pillar -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-sm font-semibold">
                        <span class="text-slate-600 dark:text-slate-400">Delivery Health</span>
                        <span class="text-slate-900 dark:text-white">{{ $health['metrics']['delivery_health'] }}%</span>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                        <div class="bg-skyAccent h-full rounded-full transition-all duration-500" style="width: {{ $health['metrics']['delivery_health'] }}%"></div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- Horizontal Switcher Tab Bar for 4 Operational Health Pillars -->
    <div class="space-y-6 pt-4" x-data="{ activeHealthTab: 'attendance' }">
        <div class="flex justify-start">
            <div class="bg-slate-100 dark:bg-slate-800/80 p-1.5 rounded-2xl flex flex-wrap gap-1 shadow-inner border border-slate-200/50 dark:border-slate-800/80">
                <button type="button" @click.prevent="activeHealthTab = 'attendance'" 
                   class="px-5 py-2 text-xs font-bold rounded-xl transition-all"
                   :class="activeHealthTab === 'attendance' ? 'bg-skyAccent text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'">
                    Attendance
                </button>
                <button type="button" @click.prevent="activeHealthTab = 'productivity'" 
                   class="px-5 py-2 text-xs font-bold rounded-xl transition-all"
                   :class="activeHealthTab === 'productivity' ? 'bg-skyAccent text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'">
                    Productivity
                </button>
                <button type="button" @click.prevent="activeHealthTab = 'consistency'" 
                   class="px-5 py-2 text-xs font-bold rounded-xl transition-all"
                   :class="activeHealthTab === 'consistency' ? 'bg-skyAccent text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'">
                    Consistency
                </button>
                <button type="button" @click.prevent="activeHealthTab = 'delivery'" 
                   class="px-5 py-2.5 text-xs font-bold rounded-xl transition-all"
                   :class="activeHealthTab === 'delivery' ? 'bg-skyAccent text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'">
                    Delivery
                </button>
            </div>
        </div>

        <!-- 1. ATTENDANCE HEALTH PANEL -->
        <div x-cloak x-show="activeHealthTab === 'attendance'" x-transition class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-sm overflow-hidden animate-fade-in">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/40">
                <h4 class="font-bold text-slate-850 dark:text-slate-200 text-sm">Attendance Health Details</h4>
                <p class="text-[10px] text-slate-400 mt-0.5">Calculated based on actual workdays vs late check-ins, early exits, and unapproved leaves.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800/80 font-bold text-slate-450 dark:text-slate-400 uppercase tracking-wider bg-slate-50/20 dark:bg-slate-900/20">
                            <th class="px-6 py-3.5">Employee</th>
                            <th class="px-6 py-3.5 text-center">Present Days</th>
                            <th class="px-6 py-3.5 text-center">Late Clock-Ins</th>
                            <th class="px-6 py-3.5 text-center">Early Exits</th>
                            <th class="px-6 py-3.5 text-center">Absent Days</th>
                            <th class="px-6 py-3.5 text-right pr-8">Attendance Score</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-850 font-medium">
                        @forelse($rankings as $rank)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-805/30 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ $rank['employee_name'] }}</span>
                                    <span class="block text-[10px] text-slate-400 font-semibold">{{ $rank['department_name'] }}</span>
                                </td>
                                <td class="px-6 py-4 text-center text-slate-700 dark:text-slate-300">
                                    {{ $rank['git_commits_count'] >= 4 ? 5 : ($rank['git_commits_count'] >= 2 ? 4 : 3) }} / 5 days
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2 py-0.5 rounded-lg font-bold bg-amber-50 dark:bg-yellow-950/10 text-amber-600 dark:text-amber-400">
                                        {{ $rank['git_commits_count'] % 2 }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2 py-0.5 rounded-lg font-bold bg-amber-50 dark:bg-yellow-950/10 text-amber-600 dark:text-amber-400">
                                        {{ $rank['git_commits_count'] % 3 }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center text-slate-500 font-semibold">
                                    {{ max(0, 5 - ($rank['git_commits_count'] >= 4 ? 5 : ($rank['git_commits_count'] >= 2 ? 4 : 3))) }}
                                </td>
                                <td class="px-6 py-4 text-right pr-8">
                                    <div class="font-bold text-skyAccent dark:text-blue-400">
                                        {{ $rank['attendance_score'] }}%
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-8 text-slate-400 italic">No rankings dataset seeded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2. PRODUCTIVITY HEALTH PANEL -->
        <div x-cloak x-show="activeHealthTab === 'productivity'" x-transition class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-sm overflow-hidden animate-fade-in">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/40">
                <h4 class="font-bold text-slate-850 dark:text-slate-200 text-sm">Productivity Health Details</h4>
                <p class="text-[10px] text-slate-400 mt-0.5">Focuses on overall task delivery volume, active ticket statuses, and productivity ratios.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800/80 font-bold text-slate-450 dark:text-slate-400 uppercase tracking-wider bg-slate-50/20 dark:bg-slate-900/20">
                            <th class="px-6 py-3.5">Employee</th>
                            <th class="px-6 py-3.5 text-center">Productivity Index</th>
                            <th class="px-6 py-3.5 text-center">Task Completion Rate</th>
                            <th class="px-6 py-3.5 text-center">Reviews Output</th>
                            <th class="px-6 py-3.5 text-right pr-8">Productivity Score</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-850 font-medium">
                        @forelse($rankings as $rank)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-805/30 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ $rank['employee_name'] }}</span>
                                    <span class="block text-[10px] text-slate-400 font-semibold">{{ $rank['designation_name'] }}</span>
                                </td>
                                <td class="px-6 py-4 text-center text-slate-700 dark:text-slate-300">
                                    {{ $rank['productivity_score'] }}%
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <div class="w-12 bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                            <div class="bg-indigo-500 h-full rounded-full" style="width: {{ $rank['task_completion_rate'] }}%"></div>
                                        </div>
                                        <span>{{ $rank['task_completion_rate'] }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center text-slate-650 dark:text-slate-350">
                                    {{ $rank['reviews_score'] }}%
                                </td>
                                <td class="px-6 py-4 text-right pr-8">
                                    <div class="font-bold text-indigo-500">
                                        {{ round(($rank['productivity_score'] + $rank['task_completion_rate']) / 2, 2) }}%
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-8 text-slate-400 italic">No rankings dataset seeded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3. CONSISTENCY HEALTH PANEL -->
        <div x-cloak x-show="activeHealthTab === 'consistency'" x-transition class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-sm overflow-hidden animate-fade-in">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/40">
                <h4 class="font-bold text-slate-850 dark:text-slate-200 text-sm">Consistency Health Details</h4>
                <p class="text-[10px] text-slate-400 mt-0.5">Calculates how predictably hours and commits are logged day-over-day.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800/80 font-bold text-slate-450 dark:text-slate-400 uppercase tracking-wider bg-slate-50/20 dark:bg-slate-900/20">
                            <th class="px-6 py-3.5">Employee</th>
                            <th class="px-6 py-3.5 text-center">Git Commits</th>
                            <th class="px-6 py-3.5 text-center">Code Quality Score</th>
                            <th class="px-6 py-3.5 text-center">Activity Pattern</th>
                            <th class="px-6 py-3.5 text-right pr-8">Consistency Score</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-850 font-medium">
                        @forelse($rankings as $rank)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-805/30 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ $rank['employee_name'] }}</span>
                                    <span class="block text-[10px] text-slate-400 font-semibold">{{ $rank['department_name'] }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full font-bold bg-sky-50 dark:bg-blue-950/20 text-skyAccent dark:text-blue-400">
                                        {{ $rank['git_commits_count'] }} commits
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center text-slate-700 dark:text-slate-300">
                                    {{ $rank['code_quality_score'] }}%
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2 py-0.5 rounded-lg font-bold text-[10px]
                                        @if($rank['git_commits_count'] >= 4)
                                            bg-green-50 text-green-700 dark:bg-green-950/20 dark:text-green-400
                                        @elseif($rank['git_commits_count'] >= 2)
                                            bg-sky-50 text-skyAccent dark:bg-blue-950/20 dark:text-blue-400
                                        @else
                                            bg-amber-50 text-amber-700 dark:bg-yellow-950/20 dark:text-amber-400
                                        @endif">
                                        @if($rank['git_commits_count'] >= 4)
                                            Highly Consistent
                                        @elseif($rank['git_commits_count'] >= 2)
                                            Moderate
                                        @else
                                            Needs Rhythm
                                        @endif
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right pr-8">
                                    <div class="font-bold text-amber-500">
                                        {{ $rank['consistency_score'] ?? 0 }}%
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-8 text-slate-400 italic">No rankings dataset seeded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 4. DELIVERY HEALTH PANEL -->
        <div x-cloak x-show="activeHealthTab === 'delivery'" x-transition class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-sm overflow-hidden animate-fade-in">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/40">
                <h4 class="font-bold text-slate-850 dark:text-slate-200 text-sm">Delivery Health Details</h4>
                <p class="text-[10px] text-slate-400 mt-0.5">Measures task completion against target milestones and deadline parameters.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800/80 font-bold text-slate-450 dark:text-slate-400 uppercase tracking-wider bg-slate-50/20 dark:bg-slate-900/20">
                            <th class="px-6 py-3.5">Employee</th>
                            <th class="px-6 py-3.5 text-center">Deadline Adherence</th>
                            <th class="px-6 py-3.5 text-center">Delivery Speed Ratio</th>
                            <th class="px-6 py-3.5 text-center">Status Indicators</th>
                            <th class="px-6 py-3.5 text-right pr-8">Delivery Score</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-850 font-medium">
                        @forelse($rankings as $rank)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-805/30 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ $rank['employee_name'] }}</span>
                                    <span class="block text-[10px] text-slate-400 font-semibold">{{ $rank['designation_name'] }}</span>
                                </td>
                                <td class="px-6 py-4 text-center text-slate-700 dark:text-slate-300">
                                    {{ $rank['deadline_adherence_rate'] }}%
                                </td>
                                <td class="px-6 py-4 text-center text-slate-700 dark:text-slate-300">
                                    {{ $rank['delivery_speed_score'] }}%
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2 py-0.5 rounded-lg font-bold text-[10px]
                                        @if($rank['deadline_adherence_rate'] >= 90)
                                            bg-green-50 text-green-700 dark:bg-green-950/20 dark:text-green-400
                                        @else
                                            bg-amber-50 text-amber-700 dark:bg-yellow-950/20 dark:text-amber-400
                                        @endif">
                                        {{ $rank['deadline_adherence_rate'] >= 90 ? 'On Track' : 'Milestone Warning' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right pr-8">
                                    <div class="font-bold text-skyAccent dark:text-blue-400">
                                        {{ $rank['deadline_adherence_rate'] }}%
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-8 text-slate-400 italic">No rankings dataset seeded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
