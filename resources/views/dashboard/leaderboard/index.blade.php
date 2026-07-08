@extends('layouts.app')

@section('content')
<div class="space-y-8 animate-fade-in" x-data="{ 
    period: '{{ $period }}',
    customOpen: '{{ $period === 'custom' ? 'true' : 'false' }}' === 'true',
    activeTab: '{{ $tab }}'
}">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Performance Leaderboards</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Track and compare engineering productivity, attendance, and overall delivery across your teams and the organization.</p>
        </div>
        <div class="bg-slate-100 dark:bg-slate-905 p-1 rounded-xl flex gap-1 shrink-0">
            <button type="button" @click.prevent="activeTab = 'individual'; window.history.replaceState(null, '', '?tab=individual&period=' + period + '&start_date={{ $startDateStr }}&end_date={{ $endDateStr }}&scope={{ request('scope', 'team') }}&department_id={{ request('department_id') }}&team_id={{ request('team_id') }}')" 
               class="px-4 py-2 text-xs font-bold rounded-lg transition-all"
               :class="activeTab === 'individual' ? 'bg-white dark:bg-slate-800 text-skyAccent dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'">
                Individual Reports
            </button>
            <button type="button" @click.prevent="activeTab = 'team'; window.history.replaceState(null, '', '?tab=team&period=' + period + '&start_date={{ $startDateStr }}&end_date={{ $endDateStr }}&scope={{ request('scope', 'team') }}&department_id={{ request('department_id') }}&team_id={{ request('team_id') }}')" 
               class="px-4 py-2 text-xs font-bold rounded-lg transition-all"
               :class="activeTab === 'team' ? 'bg-white dark:bg-slate-800 text-skyAccent dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'">
                Team Rankings
            </button>
        </div>
    </div>

    <!-- Filters & Date Range Row -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm space-y-4">
        <form action="{{ route('dashboard.leaderboard.index') }}" method="GET" class="space-y-4">
            <input type="hidden" name="tab" :value="activeTab">
            
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

            <!-- Dropdown Filters (Only for Individual Tab) -->
            <div x-cloak x-show="activeTab === 'individual'" class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                <div class="space-y-1">
                    <label for="scope" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Scope Filter</label>
                    <select name="scope" id="scope" onchange="this.form.submit()"
                            class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-xs text-slate-850 dark:text-slate-105">
                        <option value="team" {{ request('scope', 'team') === 'team' ? 'selected' : '' }}>My Scoped Reports (Direct Reports)</option>
                        <option value="all" {{ request('scope') === 'all' ? 'selected' : '' }}>Organization-Wide (All Employees)</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label for="department_id" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Department</label>
                    <select name="department_id" id="department_id" onchange="this.form.submit()"
                            class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-xs text-slate-850 dark:text-slate-105">
                        <option value="">All Departments</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1">
                    <label for="team_id" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Operational Team</label>
                    <select name="team_id" id="team_id" onchange="this.form.submit()"
                            class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-xs text-slate-850 dark:text-slate-105">
                        <option value="">All Teams</option>
                        @foreach ($teams as $t)
                            <option value="{{ $t->id }}" {{ request('team_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!-- TABS VIEW CONTAINER -->

    <!-- INDIVIDUAL TAB -->
    <div x-show="activeTab === 'individual'" class="space-y-8">
        
        <!-- Podium Top 3 -->
        @if (count($individualRankings) >= 3)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end max-w-4xl mx-auto pt-6">
                <!-- 2nd Place -->
                <div class="order-2 md:order-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm text-center transform hover:scale-102 transition-transform duration-300 relative">
                    <div class="absolute -top-5 left-1/2 -translate-x-1/2 w-10 h-10 rounded-full bg-slate-400 text-white flex items-center justify-center font-bold border-2 border-white shadow">2</div>
                    <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-650 flex items-center justify-center font-bold text-xl mx-auto mb-3 border-2 border-slate-300">
                        {{ strtoupper(substr($individualRankings[1]['employee_name'], 0, 2)) }}
                    </div>
                    <h3 class="font-bold text-slate-850 dark:text-slate-100 truncate">{{ $individualRankings[1]['employee_name'] }}</h3>
                    <p class="text-[10px] text-slate-400 font-semibold mb-2">{{ $individualRankings[1]['department_name'] }}</p>
                    <div class="inline-flex px-3 py-1 rounded-full bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300">
                        Score: {{ $individualRankings[1]['overall_score'] }}%
                    </div>
                </div>

                <!-- 1st Place -->
                <div class="order-1 md:order-2 bg-gradient-to-b from-sky-50 to-white dark:from-slate-900 dark:to-slate-900/60 border border-sky-200 dark:border-blue-900/80 rounded-3xl p-8 shadow-md text-center transform hover:scale-104 transition-transform duration-300 relative -translate-y-2">
                    <div class="absolute -top-6 left-1/2 -translate-x-1/2 w-12 h-12 rounded-full bg-amber-400 text-white flex items-center justify-center font-black text-lg border-2 border-white shadow animate-bounce">1</div>
                    <div class="w-20 h-20 rounded-full bg-amber-100 dark:bg-yellow-950/20 text-amber-500 flex items-center justify-center font-bold text-2xl mx-auto mb-4 border-2 border-amber-300 relative shadow-inner">
                        {{ strtoupper(substr($individualRankings[0]['employee_name'], 0, 2)) }}
                        <!-- Trophy Badge -->
                        <span class="absolute -bottom-1 -right-1 bg-amber-400 text-white p-1 rounded-full border border-white shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V4zm2 2v10h6V6H7z"></path></svg>
                        </span>
                    </div>
                    <h3 class="font-extrabold text-slate-900 dark:text-white text-lg truncate">{{ $individualRankings[0]['employee_name'] }}</h3>
                    <p class="text-xs text-skyAccent dark:text-blue-400 font-bold mb-3">{{ $individualRankings[0]['department_name'] }}</p>
                    <div class="inline-flex px-4 py-1.5 rounded-full bg-skyAccent hover:bg-sky-650 text-white text-sm font-bold shadow-sm">
                        Score: {{ $individualRankings[0]['overall_score'] }}%
                    </div>
                </div>

                <!-- 3rd Place -->
                <div class="order-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm text-center transform hover:scale-102 transition-transform duration-300 relative">
                    <div class="absolute -top-5 left-1/2 -translate-x-1/2 w-10 h-10 rounded-full bg-amber-600 text-white flex items-center justify-center font-bold border-2 border-white shadow">3</div>
                    <div class="w-16 h-16 rounded-full bg-amber-50 dark:bg-yellow-950/10 text-amber-700 flex items-center justify-center font-bold text-xl mx-auto mb-3 border-2 border-amber-500/60">
                        {{ strtoupper(substr($individualRankings[2]['employee_name'], 0, 2)) }}
                    </div>
                    <h3 class="font-bold text-slate-850 dark:text-slate-100 truncate">{{ $individualRankings[2]['employee_name'] }}</h3>
                    <p class="text-[10px] text-slate-400 font-semibold mb-2">{{ $individualRankings[2]['department_name'] }}</p>
                    <div class="inline-flex px-3 py-1 rounded-full bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300">
                        Score: {{ $individualRankings[2]['overall_score'] }}%
                    </div>
                </div>
            </div>
        @endif

        <!-- Individual Standings Table -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/40 flex items-center justify-between">
                <h3 class="font-bold text-slate-850 dark:text-slate-200 text-sm">Individual Standings Table</h3>
                <span class="text-xs text-slate-400">{{ count($individualRankings) }} employees ranked</span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800/80 text-[10px] font-bold text-slate-450 dark:text-slate-400 uppercase tracking-wider bg-slate-50/20 dark:bg-slate-900/20">
                            <th class="px-6 py-3.5">Rank</th>
                            <th class="px-6 py-3.5">Employee</th>
                            <th class="px-6 py-3.5">Department / Role</th>
                            <th class="px-6 py-3.5 text-center">Task Completion</th>
                            <th class="px-6 py-3.5 text-center">Attendance</th>
                            <th class="px-6 py-3.5 text-center">Code Quality</th>
                            <th class="px-6 py-3.5 text-center">Git Activity</th>
                            <th class="px-6 py-3.5 text-right pr-8">Performance Score</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-850">
                        @forelse (count($individualRankings) >= 3 ? array_slice($individualRankings, 3) : $individualRankings as $rank)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-805/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        @if ($rank['rank'] === 1)
                                            <span class="w-6 h-6 rounded-full bg-amber-400 text-white font-bold text-xs flex items-center justify-center">1</span>
                                        @elseif ($rank['rank'] === 2)
                                            <span class="w-6 h-6 rounded-full bg-slate-300 text-slate-800 font-bold text-xs flex items-center justify-center">2</span>
                                        @elseif ($rank['rank'] === 3)
                                            <span class="w-6 h-6 rounded-full bg-amber-600 text-white font-bold text-xs flex items-center justify-center">3</span>
                                        @else
                                            <span class="w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 font-bold text-xs flex items-center justify-center">{{ $rank['rank'] }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-800 dark:text-slate-200">
                                        <a href="{{ route('dashboard.employees.show', $rank['employee_id']) }}" class="hover:text-skyAccent">
                                            {{ $rank['employee_name'] }}
                                        </a>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="block text-xs font-semibold text-slate-650 dark:text-slate-350">{{ $rank['department_name'] }}</span>
                                    <span class="block text-[10px] text-slate-400">{{ $rank['designation_name'] }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ $rank['task_completion_rate'] }}%</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ $rank['attendance_score'] }}%</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ $rank['code_quality_score'] }}%</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-sky-50 dark:bg-blue-950/20 text-skyAccent dark:text-blue-400">
                                        {{ $rank['git_commits_count'] }} commits
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right pr-8">
                                    <div class="inline-flex items-center gap-1.5 font-bold text-sm text-skyAccent dark:text-blue-400">
                                        {{ $rank['overall_score'] }}%
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-10 text-xs text-slate-405 italic">
                                    No data available for this range. Seed the database.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TEAM TAB -->
    <div x-cloak x-show="activeTab === 'team'" class="space-y-8">

        <!-- Podium Top 3 for Teams -->
        @if (count($teamRankings) >= 3)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end max-w-4xl mx-auto pt-6 mb-8">
                <!-- 2nd Place -->
                <div class="order-2 md:order-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm text-center transform hover:scale-102 transition-transform duration-300 relative">
                    <div class="absolute -top-5 left-1/2 -translate-x-1/2 w-10 h-10 rounded-full bg-slate-400 text-white flex items-center justify-center font-bold border-2 border-white shadow">2</div>
                    <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-650 flex items-center justify-center font-bold text-xl mx-auto mb-3 border-2 border-slate-300">
                        {{ strtoupper(substr($teamRankings[1]['team_name'], 0, 2)) }}
                    </div>
                    <h3 class="font-bold text-slate-850 dark:text-slate-100 truncate">
                        <a href="{{ route('dashboard.teams.show', $teamRankings[1]['team_id']) }}" class="hover:text-skyAccent">
                            {{ $teamRankings[1]['team_name'] }}
                        </a>
                    </h3>
                    <p class="text-[10px] text-slate-400 font-semibold mb-2">{{ $teamRankings[1]['members_count'] }} members</p>
                    <div class="inline-flex px-3 py-1 rounded-full bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300">
                        Score: {{ $teamRankings[1]['overall_score'] }}%
                    </div>
                </div>

                <!-- 1st Place -->
                <div class="order-1 md:order-2 bg-gradient-to-b from-sky-50 to-white dark:from-slate-900 dark:to-slate-900/60 border border-sky-200 dark:border-blue-900/80 rounded-3xl p-8 shadow-md text-center transform hover:scale-104 transition-transform duration-300 relative -translate-y-2">
                    <div class="absolute -top-6 left-1/2 -translate-x-1/2 w-12 h-12 rounded-full bg-amber-400 text-white flex items-center justify-center font-black text-lg border-2 border-white shadow animate-bounce">1</div>
                    <div class="w-20 h-20 rounded-full bg-amber-100 dark:bg-yellow-950/20 text-amber-500 flex items-center justify-center font-bold text-2xl mx-auto mb-4 border-2 border-amber-300 relative shadow-inner">
                        {{ strtoupper(substr($teamRankings[0]['team_name'], 0, 2)) }}
                    </div>
                    <h3 class="font-black text-slate-900 dark:text-white text-lg mb-1 truncate">
                        <a href="{{ route('dashboard.teams.show', $teamRankings[0]['team_id']) }}" class="hover:text-skyAccent">
                            {{ $teamRankings[0]['team_name'] }}
                        </a>
                    </h3>
                    <p class="text-xs text-amber-600 dark:text-amber-400 font-bold mb-3">{{ $teamRankings[0]['members_count'] }} members</p>
                    <div class="inline-flex px-4 py-1.5 rounded-full bg-skyAccent text-white text-xs font-extrabold shadow-sm">
                        Score: {{ $teamRankings[0]['overall_score'] }}%
                    </div>
                </div>

                <!-- 3rd Place -->
                <div class="order-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm text-center transform hover:scale-102 transition-transform duration-300 relative">
                    <div class="absolute -top-5 left-1/2 -translate-x-1/2 w-10 h-10 rounded-full bg-amber-600 text-white flex items-center justify-center font-bold border-2 border-white shadow">3</div>
                    <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-650 flex items-center justify-center font-bold text-xl mx-auto mb-3 border-2 border-slate-300">
                        {{ strtoupper(substr($teamRankings[2]['team_name'], 0, 2)) }}
                    </div>
                    <h3 class="font-bold text-slate-850 dark:text-slate-100 truncate">
                        <a href="{{ route('dashboard.teams.show', $teamRankings[2]['team_id']) }}" class="hover:text-skyAccent">
                            {{ $teamRankings[2]['team_name'] }}
                        </a>
                    </h3>
                    <p class="text-[10px] text-slate-400 font-semibold mb-2">{{ $teamRankings[2]['members_count'] }} members</p>
                    <div class="inline-flex px-3 py-1 rounded-full bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300">
                        Score: {{ $teamRankings[2]['overall_score'] }}%
                    </div>
                </div>
            </div>
        @endif

        <!-- Team Rankings Table -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/40 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 dark:text-slate-200 text-sm">Operational Team Rankings</h3>
                <span class="text-xs text-slate-400">{{ count($teamRankings) }} teams ranked</span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800/80 text-[10px] font-bold text-slate-450 dark:text-slate-400 uppercase tracking-wider bg-slate-50/20 dark:bg-slate-900/20">
                            <th class="px-6 py-3.5">Rank</th>
                            <th class="px-6 py-3.5">Team</th>
                            <th class="px-6 py-3.5 text-center">Productivity</th>
                            <th class="px-6 py-3.5 text-center">Delivery Speed</th>
                            <th class="px-6 py-3.5 text-center">Attendance Score</th>
                            <th class="px-6 py-3.5 text-center">Code Quality</th>
                            <th class="px-6 py-3.5 text-center">Collaboration</th>
                            <th class="px-6 py-3.5 text-right pr-8">Overall Team Score</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-850">
                        @forelse (count($teamRankings) >= 3 ? array_slice($teamRankings, 3) : $teamRankings as $rank)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-805/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        @if ($rank['rank'] === 1)
                                            <span class="w-6 h-6 rounded-full bg-amber-400 text-white font-bold text-xs flex items-center justify-center">1</span>
                                        @elseif ($rank['rank'] === 2)
                                            <span class="w-6 h-6 rounded-full bg-slate-300 text-slate-800 font-bold text-xs flex items-center justify-center">2</span>
                                        @elseif ($rank['rank'] === 3)
                                            <span class="w-6 h-6 rounded-full bg-amber-600 text-white font-bold text-xs flex items-center justify-center">3</span>
                                        @else
                                            <span class="w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 font-bold text-xs flex items-center justify-center">{{ $rank['rank'] }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-800 dark:text-slate-200">
                                        <a href="{{ route('dashboard.teams.show', $rank['team_id']) }}" class="hover:text-skyAccent">
                                            {{ $rank['team_name'] }}
                                        </a>
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-semibold">{{ $rank['members_count'] }} members</div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ $rank['productivity'] }}%</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ $rank['delivery'] }}%</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ $rank['attendance'] }}%</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ $rank['code_quality'] }}%</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ $rank['collaboration'] }}%</span>
                                </td>
                                <td class="px-6 py-4 text-right pr-8">
                                    <div class="inline-flex items-center gap-1.5 font-bold text-sm text-skyAccent dark:text-blue-400">
                                        {{ $rank['overall_score'] }}%
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-10 text-xs text-slate-405 italic">
                                    No data available. Form operational teams.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
