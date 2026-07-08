@extends('layouts.app')

@section('content')
<div class="space-y-8 animate-fade-in" x-data="meetingDashboardState()">
    <!-- Header/Back Nav -->
    <div class="space-y-4">
        <a href="{{ route('dashboard.teams.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-skyAccent dark:text-slate-400 dark:hover:text-blue-400 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to Teams
        </a>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-sky-50 dark:bg-blue-950/20 text-skyAccent dark:text-blue-400 flex items-center justify-center font-bold text-lg border border-slate-200 dark:border-slate-800">
                    {{ substr($team->name, 0, 2) }}
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $team->name }}</h2>
                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Team supervised by you</span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-450 uppercase">Team Health:</span>
                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold border 
                    @if(($teamMetrics['manager_score'] ?? 0) >= 80)
                        bg-green-50 text-green-700 border-green-200 dark:bg-green-950/20 dark:text-green-400 dark:border-green-800
                    @elseif(($teamMetrics['manager_score'] ?? 0) >= 60)
                        bg-sky-50 text-skyAccent border-sky-200 dark:bg-blue-950/20 dark:text-blue-400 dark:border-blue-800
                    @else
                        bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-950/20 dark:text-orange-400 dark:border-orange-800
                    @endif">
                    {{ ($teamMetrics['manager_score'] ?? 0) >= 80 ? 'Excellent' : (($teamMetrics['manager_score'] ?? 0) >= 60 ? 'Healthy' : 'Needs Attention') }}
                </span>
            </div>
        </div>
    </div>

    <!-- Tabs Nav -->
    <div class="border-b border-slate-200 dark:border-slate-800">
        <nav class="flex gap-6 -mb-px">
            <button @click="activeTab = 'overview'" :class="activeTab === 'overview' ? 'border-skyAccent text-skyAccent dark:border-blue-400 dark:text-blue-400 font-bold' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 hover:border-slate-300 font-medium'" class="pb-4 border-b-2 text-sm transition-all">
                Overview & AI Report
            </button>
            <button @click="activeTab = 'tasks'" :class="activeTab === 'tasks' ? 'border-skyAccent text-skyAccent dark:border-blue-400 dark:text-blue-400 font-bold' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 hover:border-slate-300 font-medium'" class="pb-4 border-b-2 text-sm transition-all">
                Team Tasks ({{ $tasks->count() }})
            </button>
            <button @click="activeTab = 'meetings'" :class="activeTab === 'meetings' ? 'border-skyAccent text-skyAccent dark:border-blue-400 dark:text-blue-400 font-bold' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 hover:border-slate-300 font-medium'" class="pb-4 border-b-2 text-sm transition-all">
                Meetings & Notes ({{ $meetings->count() }})
            </button>
        </nav>
    </div>

    <!-- Tab Contents -->
    <div>
        <!-- TAB 1: OVERVIEW & AI REPORT -->
        <div x-show="activeTab === 'overview'" class="space-y-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Metrics Card -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 flex flex-col items-center justify-center text-center shadow-sm space-y-4">
                    <span class="text-xs font-bold text-slate-450 uppercase tracking-widest">Operational Score</span>
                    <div class="relative w-36 h-36 rounded-full border-8 border-slate-100 dark:border-slate-800 flex items-center justify-center shadow-inner">
                        <div class="text-center">
                            <span class="text-3xl font-extrabold text-slate-900 dark:text-white">{{ $teamMetrics['manager_score'] ?? 0 }}%</span>
                            <span class="text-[10px] text-slate-400 block mt-0.5">Weighted avg</span>
                        </div>
                    </div>
                    
                    <div class="w-full text-left space-y-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <div class="flex justify-between text-xs">
                            <span class="text-slate-500 font-medium">Task Completion:</span>
                            <span class="font-bold text-slate-850 dark:text-slate-200">{{ $teamMetrics['task_completion_rate'] ?? 0 }}%</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-slate-500 font-medium">Deadline Adherence:</span>
                            <span class="font-bold text-slate-850 dark:text-slate-200">{{ $teamMetrics['deadline_adherence_rate'] ?? 0 }}%</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-slate-500 font-medium">Work Consistency:</span>
                            <span class="font-bold text-slate-850 dark:text-slate-200">{{ $teamMetrics['consistency_score'] ?? 0 }}%</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-slate-500 font-medium">Logged Productivity:</span>
                            <span class="font-bold text-slate-850 dark:text-slate-200">{{ $teamMetrics['productivity_score'] ?? 0 }}%</span>
                        </div>
                    </div>
                </div>

                <!-- Team Members Card -->
                <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm flex flex-col justify-between">
                    <div>
                        <h3 class="font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3 mb-4 flex items-center justify-between">
                            <span>Team Members</span>
                            <span class="px-2 py-0.5 rounded bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-bold">{{ $team->members->count() }} members</span>
                        </h3>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-h-48 overflow-y-auto pr-1">
                            @forelse($team->members as $member)
                                <div class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-slate-800/50">
                                    <div class="w-8 h-8 rounded-full bg-skyAccent/10 text-skyAccent dark:bg-blue-950/30 dark:text-blue-400 flex items-center justify-center font-bold text-xs">
                                        {{ substr($member->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <a href="{{ route('dashboard.employees.show', $member->id) }}" class="block text-xs font-bold text-slate-800 hover:text-skyAccent dark:text-slate-200 dark:hover:text-blue-400 transition-colors">
                                            {{ $member->name }}
                                        </a>
                                        <span class="block text-[10px] text-slate-400 truncate max-w-[150px]">{{ $member->email }}</span>
                                    </div>
                                </div>
                            @empty
                                <div class="col-span-2 text-center py-6 text-xs text-slate-450 italic">
                                    No members formed in this team. Update the team.
                                </div>
                            @endforelse
                        </div>
                    </div>
                    
                    <div class="text-[11px] text-slate-400 pt-4 border-t border-slate-100 dark:border-slate-800 mt-4">
                        To add or remove team members, please dissolve this team and reform it.
                    </div>
                </div>
            </div>

            <!-- AI Insights Panel -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 shadow-sm space-y-6">
                <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
                    <div class="p-2 bg-purple-50 dark:bg-purple-950/20 text-purple-500 rounded-xl">
                        <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">AI Team Analysis</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Qualitative team health report and predictive metrics assessment.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Rating and Summary -->
                    <div class="lg:col-span-1 space-y-4 bg-slate-50 dark:bg-slate-800/20 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/40">
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">AI Health Rating</span>
                            <span class="inline-flex px-3 py-1 rounded-full text-xs font-extrabold tracking-wide uppercase border 
                                @if(($insights['team_health'] ?? '') === 'Excellent')
                                    bg-green-50 text-green-700 border-green-200 dark:bg-green-950/25 dark:text-green-400 dark:border-green-900/30
                                @elseif(($insights['team_health'] ?? '') === 'Healthy')
                                    bg-sky-50 text-skyAccent border-sky-200 dark:bg-blue-950/25 dark:text-blue-400 dark:border-blue-900/30
                                @else
                                    bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/25 dark:text-rose-400 dark:border-rose-900/30
                                @endif">
                                {{ $insights['team_health'] ?? 'Unrated' }}
                            </span>
                        </div>
                        
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Executive Summary</span>
                            <p class="text-xs text-slate-650 dark:text-slate-300 leading-relaxed font-medium">
                                {{ $insights['summary'] ?? 'No qualitative analysis available.' }}
                            </p>
                        </div>
                    </div>

                    <!-- Strengths, weaknesses, risk, and recs -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Strengths -->
                            <div class="space-y-3">
                                <span class="text-xs font-bold text-emerald-605 flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-550"></span>
                                    Team Strengths
                                </span>
                                <ul class="space-y-2">
                                    @forelse($insights['strengths'] ?? [] as $strength)
                                        <li class="text-xs text-slate-650 dark:text-slate-300 flex items-start gap-2">
                                            <svg class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            <span>{{ $strength }}</span>
                                        </li>
                                    @empty
                                        <li class="text-xs text-slate-400 italic">No specific team strengths recorded.</li>
                                    @endforelse
                                </ul>
                            </div>

                            <!-- Weaknesses -->
                            <div class="space-y-3">
                                <span class="text-xs font-bold text-amber-605 flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-550"></span>
                                    Operational Bottlenecks
                                </span>
                                <ul class="space-y-2">
                                    @forelse($insights['weaknesses'] ?? [] as $weakness)
                                        <li class="text-xs text-slate-650 dark:text-slate-300 flex items-start gap-2">
                                            <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                            <span>{{ $weakness }}</span>
                                        </li>
                                    @empty
                                        <li class="text-xs text-slate-400 italic">No weaknesses identified.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-100 dark:border-slate-800 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Risks -->
                            <div class="space-y-3">
                                <span class="text-xs font-bold text-red-605 flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-550"></span>
                                    Identified Risks
                                </span>
                                <ul class="space-y-2">
                                    @forelse($insights['risks'] ?? [] as $risk)
                                        <li class="text-xs text-slate-650 dark:text-slate-300 flex items-start gap-2">
                                            <svg class="w-4 h-4 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            <span>{{ $risk }}</span>
                                        </li>
                                    @empty
                                        <li class="text-xs text-slate-400 italic">No operational risks detected.</li>
                                    @endforelse
                                </ul>
                            </div>

                            <!-- Recommendations -->
                            <div class="space-y-3">
                                <span class="text-xs font-bold text-indigo-650 flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-550"></span>
                                    Recommended Adjustments
                                </span>
                                <ul class="space-y-2">
                                    @forelse($insights['recommendations'] ?? [] as $rec)
                                        <li class="text-xs text-slate-650 dark:text-slate-300 flex items-start gap-2">
                                            <svg class="w-4 h-4 text-indigo-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364.364l-.707.707M21 12h-1M4 9H3m15.364 6.364l-.707-.707M6.343 6.343l.707-.707m9.9 5.05a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                            <span>{{ $rec }}</span>
                                        </li>
                                    @empty
                                        <li class="text-xs text-slate-400 italic">No adjustments proposed.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: TEAM TASKS BOARD -->
        <div x-show="activeTab === 'tasks'" class="space-y-6">
            <!-- Tasks Actions -->
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Assigned Tasks</h3>
                <button @click="showTaskModal = true" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-skyAccent hover:bg-sky-650 text-white shadow transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Assign Task to Team
                </button>
            </div>

            <!-- Tasks Table -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/40 text-slate-500 dark:text-slate-400 text-xs font-bold uppercase border-b border-slate-200 dark:border-slate-800">
                                <th class="px-6 py-4">Task Name</th>
                                <th class="px-6 py-4">Individual Assignee</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Deadline</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                            @forelse($tasks as $task)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                                    <td class="px-6 py-4">
                                        <span class="block font-semibold text-slate-900 dark:text-white">{{ $task->title }}</span>
                                        <span class="block text-xs text-slate-400 max-w-sm truncate">{{ $task->description ?? 'No description.' }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($task->assignee)
                                            <span class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-700 dark:text-slate-300">
                                                <span class="w-2 h-2 rounded-full bg-skyAccent"></span>
                                                {{ $task->assignee->name }}
                                            </span>
                                        @else
                                            <span class="text-xs text-slate-400 italic">Unassigned (Team Shared)</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($task->status === 'completed')
                                            <span class="inline-flex px-2 py-0.5 rounded-md bg-green-50 dark:bg-green-950/20 text-green-700 dark:text-green-400 text-xs font-semibold">Completed</span>
                                        @elseif ($task->status === 'in_progress')
                                            <span class="inline-flex px-2 py-0.5 rounded-md bg-sky-50 dark:bg-sky-950/20 text-skyAccent dark:text-sky-400 text-xs font-semibold">In Progress</span>
                                        @else
                                            <span class="inline-flex px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-semibold">Pending</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs font-medium text-slate-500">
                                        {{ $task->deadline ? $task->deadline->format('M d, Y') : 'No Limit' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        @if ($task->status !== 'completed')
                                            <form action="{{ route('dashboard.tasks.update', $task->id) }}" method="POST" class="inline-block">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="completed">
                                                <button type="submit" class="text-xs font-bold text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300">
                                                    Complete
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-400">Archived</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-slate-505 dark:text-slate-405">No tasks found for this team.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 3: MEETINGS & MEETING NOTES -->
        <div x-show="activeTab === 'meetings'" class="space-y-6">
            
            <!-- Tab Sub-Navigation & Actions -->
            <div class="border-b border-slate-200 dark:border-slate-800 flex flex-wrap gap-4 items-center justify-between pb-3">
                <nav class="flex flex-wrap gap-2">
                    <button @click="meetingsSubTab = 'upcoming'" :class="meetingsSubTab === 'upcoming' ? 'bg-skyAccent/10 text-skyAccent dark:bg-blue-950/20 dark:text-blue-400 font-bold' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-350'" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all">
                        Upcoming Meetings
                    </button>
                    <button @click="meetingsSubTab = 'history'" :class="meetingsSubTab === 'history' ? 'bg-skyAccent/10 text-skyAccent dark:bg-blue-950/20 dark:text-blue-400 font-bold' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-350'" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all">
                        Meeting History
                    </button>
                    <button @click="meetingsSubTab = 'notes'" :class="meetingsSubTab === 'notes' ? 'bg-skyAccent/10 text-skyAccent dark:bg-blue-950/20 dark:text-blue-400 font-bold' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-350'" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all">
                        Meeting Notes
                    </button>
                    <button @click="meetingsSubTab = 'action_items'" :class="meetingsSubTab === 'action_items' ? 'bg-skyAccent/10 text-skyAccent dark:bg-blue-950/20 dark:text-blue-400 font-bold' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-350'" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all">
                        Action Items
                    </button>
                    <button @click="meetingsSubTab = 'decisions'" :class="meetingsSubTab === 'decisions' ? 'bg-skyAccent/10 text-skyAccent dark:bg-blue-950/20 dark:text-blue-400 font-bold' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-350'" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all">
                        Decisions
                    </button>
                </nav>
                <div class="flex items-center gap-2">
                    <button @click="showMeetingModal = true" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-skyAccent hover:bg-sky-650 text-white shadow transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Schedule Meeting
                    </button>
                </div>
            </div>

            <!-- Dynamic Search & Filters Toolbar -->
            <div class="flex flex-wrap gap-4 items-center justify-between bg-slate-50 dark:bg-slate-900/60 p-4 rounded-2xl border border-slate-100 dark:border-slate-800/50">
                <div class="flex-1 min-w-[200px] relative">
                    <input type="text" x-model="meetingsSearchQuery" placeholder="Search title, description, or transcripts..." 
                           class="w-full pl-9 pr-4 py-2 border border-slate-200 dark:border-slate-800 bg-transparent text-xs rounded-xl outline-none text-slate-800 dark:text-slate-150">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>

                <!-- History date range filters -->
                <div x-show="meetingsSubTab === 'history'" class="flex flex-wrap items-center gap-3">
                    <select x-model="historyFilter" class="px-3 py-2 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs rounded-xl outline-none text-slate-800 dark:text-slate-100">
                        <option value="all">All History</option>
                        <option value="daily">Daily (Today)</option>
                        <option value="weekly">Weekly (Past 7 Days)</option>
                        <option value="monthly">Monthly (Past 30 Days)</option>
                        <option value="custom">Custom Range</option>
                    </select>
                    <div x-cloak x-show="historyFilter === 'custom'" class="flex items-center gap-2">
                        <input type="date" x-model="customStartDate" class="px-2.5 py-1.5 border border-slate-200 dark:border-slate-800 bg-transparent text-xs rounded-xl outline-none text-slate-800 dark:text-slate-150">
                        <span class="text-xs text-slate-400">to</span>
                        <input type="date" x-model="customEndDate" class="px-2.5 py-1.5 border border-slate-200 dark:border-slate-800 bg-transparent text-xs rounded-xl outline-none text-slate-800 dark:text-slate-150">
                    </div>
                </div>

                <!-- Action Items filters -->
                <div x-show="meetingsSubTab === 'action_items'" class="flex items-center gap-3">
                    <select x-model="actionStatusFilter" class="px-3 py-2 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs rounded-xl outline-none text-slate-800 dark:text-slate-100">
                        <option value="all">All Statuses</option>
                        <option value="Pending">Pending</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                    </select>
                    <select x-model="actionPriorityFilter" class="px-3 py-2 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs rounded-xl outline-none text-slate-800 dark:text-slate-100">
                        <option value="all">All Priorities</option>
                        <option value="High">High</option>
                        <option value="Medium">Medium</option>
                        <option value="Low">Low</option>
                    </select>
                </div>
            </div>

            <!-- Sub-tab Content Panels -->
            <div>
                <!-- Sub-tab 1: Upcoming Meetings -->
                <div x-show="meetingsSubTab === 'upcoming'" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <template x-for="meeting in meetings.filter(m => m.status === 'Scheduled' && (meetingsSearchQuery === '' || m.title.toLowerCase().includes(meetingsSearchQuery.toLowerCase())))" :key="meeting.id">
                            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-4 flex flex-col justify-between hover:shadow transition-shadow">
                                <div class="space-y-3">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="space-y-1">
                                            <span class="block text-sm font-bold text-slate-850 dark:text-slate-200" x-text="meeting.title"></span>
                                            <span class="block text-[11px] text-slate-450">
                                                Scheduled: <strong class="text-slate-700 dark:text-slate-350" x-text="formatDate(meeting.meeting_date)"></strong> @ <strong class="text-slate-700 dark:text-slate-350" x-text="formatTime(meeting.meeting_time)"></strong>
                                            </span>
                                        </div>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold border bg-blue-50 text-blue-700 border-blue-100 dark:bg-blue-950/20 dark:text-blue-400 dark:border-blue-900/30">
                                            Scheduled
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500 leading-relaxed font-medium" x-text="meeting.description || 'No agenda description provided.'"></p>
                                    
                                    <div class="text-[10px] text-slate-400">
                                        Duration: <span class="font-bold text-slate-650 dark:text-slate-300" x-text="meeting.duration + ' mins'"></span>
                                        <span class="mx-2">|</span>
                                        Created by: <span class="font-bold text-slate-650 dark:text-slate-300" x-text="meeting.creator_name"></span>
                                    </div>
                                </div>

                                <div class="pt-4 border-t border-slate-100 dark:border-slate-850/60 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <a :href="`/dashboard/meetings/${meeting.id}`" class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-slate-50 border border-slate-250 hover:bg-slate-100 text-slate-700 dark:bg-slate-800 dark:border-slate-750 dark:text-slate-300 dark:hover:bg-slate-750 transition-colors">
                                            View Details
                                        </a>
                                        <template x-if="meeting.meeting_link">
                                            <a :href="meeting.meeting_link" target="_blank" class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 text-emerald-700 dark:bg-green-950/20 dark:border-green-900/30 dark:text-green-400 dark:hover:bg-green-900/40 transition-colors">
                                                Join Meeting
                                            </a>
                                        </template>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <button @click="openReschedule(meeting)" class="text-xs font-bold text-sky-600 hover:text-sky-800 dark:text-blue-400 dark:hover:text-blue-300 px-2 py-1">
                                            Reschedule
                                        </button>
                                        <button @click="completeMeeting(meeting.id)" class="text-xs font-bold text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 px-2 py-1">
                                            Complete
                                        </button>
                                        <button @click="cancelMeeting(meeting.id)" class="text-xs font-bold text-rose-600 hover:text-rose-800 dark:text-red-400 dark:hover:text-red-300 px-2 py-1">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div x-show="meetings.filter(m => m.status === 'Scheduled').length === 0" class="col-span-2 text-center py-12 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6">
                            <span class="text-xs text-slate-400 italic">No upcoming meetings scheduled.</span>
                        </div>
                    </div>
                </div>

                <!-- Sub-tab 2: Meeting History -->
                <div x-show="meetingsSubTab === 'history'" class="space-y-4">
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-800/40 text-slate-500 dark:text-slate-400 text-xs font-bold uppercase border-b border-slate-200 dark:border-slate-800">
                                        <th class="px-6 py-4">Meeting Title</th>
                                        <th class="px-6 py-4">Date & Time</th>
                                        <th class="px-6 py-4">Duration</th>
                                        <th class="px-6 py-4">Status</th>
                                        <th class="px-6 py-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                                    <template x-for="meeting in meetings.filter(m => {
                                        if (m.status === 'Scheduled') return false;
                                        if (meetingsSearchQuery !== '' && !m.title.toLowerCase().includes(meetingsSearchQuery.toLowerCase())) return false;
                                        
                                        if (historyFilter === 'daily') {
                                            const today = new Date().toISOString().split('T')[0];
                                            return m.meeting_date === today;
                                        } else if (historyFilter === 'weekly') {
                                            const cutoff = new Date();
                                            cutoff.setDate(cutoff.getDate() - 7);
                                            return new Date(m.meeting_date) >= cutoff;
                                        } else if (historyFilter === 'monthly') {
                                            const cutoff = new Date();
                                            cutoff.setDate(cutoff.getDate() - 30);
                                            return new Date(m.meeting_date) >= cutoff;
                                        } else if (historyFilter === 'custom' && customStartDate && customEndDate) {
                                            const d = new Date(m.meeting_date);
                                            return d >= new Date(customStartDate) && d <= new Date(customEndDate);
                                        }
                                        return true;
                                    })" :key="meeting.id">
                                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                                            <td class="px-6 py-4">
                                                <span class="block font-semibold text-slate-900 dark:text-white" x-text="meeting.title"></span>
                                                <span class="block text-xs text-slate-400 max-w-sm truncate" x-text="meeting.description || 'No agenda description.'"></span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600 dark:text-slate-300">
                                                <span x-text="formatDate(meeting.meeting_date) + ' @ ' + formatTime(meeting.meeting_time)"></span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500" x-text="meeting.duration + ' mins'"></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-semibold border"
                                                      :class="meeting.status === 'Completed' ? 'bg-green-50 text-green-700 border-green-200 dark:bg-green-950/20 dark:text-green-400 dark:border-green-800' : 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/20 dark:text-red-400 dark:border-red-800'"
                                                      x-text="meeting.status"></span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-xs">
                                                <a :href="`/dashboard/meetings/${meeting.id}`" class="font-bold text-skyAccent hover:text-sky-650 dark:text-blue-400 dark:hover:text-blue-300">View Detail</a>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Sub-tab 3: Meeting Notes -->
                <div x-show="meetingsSubTab === 'notes'" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <template x-for="meeting in meetings.filter(m => m.status === 'Completed' && (meetingsSearchQuery === '' || m.title.toLowerCase().includes(meetingsSearchQuery.toLowerCase()) || (m.transcript && (m.transcript.summary.toLowerCase().includes(meetingsSearchQuery.toLowerCase()) || m.transcript.transcript.toLowerCase().includes(meetingsSearchQuery.toLowerCase())))))" :key="meeting.id">
                            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-4 flex flex-col justify-between hover:shadow transition-shadow">
                                <div class="space-y-3">
                                    <div class="flex items-start justify-between">
                                        <div class="space-y-1">
                                            <span class="block text-sm font-bold text-slate-850 dark:text-slate-200" x-text="meeting.title"></span>
                                            <span class="block text-[10px] text-slate-450" x-text="formatDate(meeting.meeting_date)"></span>
                                        </div>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-100 dark:bg-purple-950/20 dark:text-purple-400 dark:border-purple-900/30 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 text-purple-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>
                                            AI Notes Ready
                                        </span>
                                    </div>
                                    
                                    <div class="p-3.5 bg-slate-50 dark:bg-slate-805/30 border border-slate-100 dark:border-slate-800/40 rounded-xl space-y-2">
                                        <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider">AI Summary Card</span>
                                        <p class="text-xs text-slate-650 dark:text-slate-350 whitespace-pre-line leading-relaxed font-medium" 
                                           x-text="meeting.transcript ? meeting.transcript.summary : 'Meeting summary has not been loaded from Fireflies yet.'"></p>
                                    </div>
                                </div>

                                <div class="pt-4 border-t border-slate-100 dark:border-slate-850/60 flex items-center justify-between">
                                    <a :href="`/dashboard/meetings/${meeting.id}`" class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-slate-50 border border-slate-250 hover:bg-slate-100 text-slate-700 dark:bg-slate-800 dark:border-slate-750 dark:text-slate-300 dark:hover:bg-slate-750 transition-colors">
                                        Open Detailed Transcript
                                    </a>
                                    <button @click="syncFireflies(meeting.id)" class="text-xs font-bold text-sky-600 hover:text-sky-850 dark:text-blue-400 dark:hover:text-blue-300 px-2 py-1">
                                        Sync with Fireflies
                                    </button>
                                </div>
                            </div>
                        </template>
                        <div x-show="meetings.filter(m => m.status === 'Completed').length === 0" class="col-span-2 text-center py-12 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6">
                            <span class="text-xs text-slate-400 italic">No completed meetings with transcripts. Complete a meeting to pull AI notes.</span>
                        </div>
                    </div>
                </div>

                <!-- Sub-tab 4: Action Items -->
                <div x-show="meetingsSubTab === 'action_items'" class="space-y-4">
                    <div class="flex items-center justify-between pb-2">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Extracted Team Action Items</span>
                        <div class="flex items-center gap-2">
                            <select x-model="addActionMeetingId" class="px-2 py-1.5 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs rounded-xl outline-none text-slate-800 dark:text-slate-150">
                                <option value="">Select Meeting to Add Task...</option>
                                <template x-for="meeting in meetings" :key="meeting.id">
                                    <option :value="meeting.id" x-text="meeting.title"></option>
                                </template>
                            </select>
                            <button @click="if (addActionMeetingId) { openAddAction(addActionMeetingId) } else { alert('Please select a meeting first') }" 
                                    class="px-2.5 py-1.5 bg-skyAccent hover:bg-sky-650 text-white rounded-lg text-xs font-bold shadow">
                                + Add Action Item
                            </button>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-800/40 text-slate-500 dark:text-slate-400 text-xs font-bold uppercase border-b border-slate-200 dark:border-slate-800">
                                        <th class="px-6 py-4">Action Item Task</th>
                                        <th class="px-6 py-4">Assigned Employee</th>
                                        <th class="px-6 py-4">Due Date</th>
                                        <th class="px-6 py-4">Priority</th>
                                        <th class="px-6 py-4">Status</th>
                                        <th class="px-6 py-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                                    <template x-for="item in getActionItems().filter(item => {
                                        if (actionStatusFilter !== 'all' && item.status !== actionStatusFilter) return false;
                                        if (actionPriorityFilter !== 'all' && item.priority !== actionPriorityFilter) return false;
                                        if (meetingsSearchQuery !== '' && !item.action_item.toLowerCase().includes(meetingsSearchQuery.toLowerCase()) && !item.meeting_title.toLowerCase().includes(meetingsSearchQuery.toLowerCase())) return false;
                                        return true;
                                    })" :key="item.id">
                                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                                            <td class="px-6 py-4">
                                                <span class="block font-semibold text-slate-900 dark:text-white" x-text="item.action_item"></span>
                                                <span class="block text-[10px] text-slate-450 italic">
                                                    Meeting: <a :href="`/dashboard/meetings/${item.meeting_id}`" class="underline text-skyAccent dark:text-blue-400 font-bold" x-text="item.meeting_title"></a>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <!-- Dynamic Assignee Selection -->
                                                <select :value="item.assigned_to" @change="updateActionAssignee(item.id, $event.target.value)"
                                                        class="px-2 py-1.5 border border-slate-205 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs rounded-xl outline-none text-slate-800 dark:text-slate-150">
                                                    <option value="">Unassigned</option>
                                                    <template x-for="m in teamMembers" :key="m.id">
                                                        <option :value="m.id" x-text="m.name" :selected="m.id == item.assigned_to"></option>
                                                    </template>
                                                </select>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600 dark:text-slate-350">
                                                <input type="date" :value="item.due_date" 
                                                       @change="fetch(`/dashboard/action-items/${item.id}/update`, {
                                                           method: 'POST',
                                                           headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                                           body: JSON.stringify({ due_date: $event.target.value, status: item.status })
                                                       })"
                                                       class="bg-transparent border-none p-0 outline-none text-xs outline-none text-slate-700 dark:text-slate-300">
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <select :value="item.priority" @change="fetch(`/dashboard/action-items/${item.id}/update`, {
                                                            method: 'POST',
                                                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                                            body: JSON.stringify({ priority: $event.target.value, status: item.status })
                                                        })"
                                                        class="px-1.5 py-0.5 border border-transparent bg-slate-50 dark:bg-slate-800 text-xs rounded font-bold uppercase"
                                                        :class="item.priority === 'High' ? 'text-red-650' : (item.priority === 'Medium' ? 'text-amber-650' : 'text-green-650')">
                                                    <option value="High" class="text-red-600">High</option>
                                                    <option value="Medium" class="text-amber-600">Medium</option>
                                                    <option value="Low" class="text-green-600">Low</option>
                                                </select>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <!-- Dynamic Status selection -->
                                                <select :value="item.status" @change="updateActionStatus(item.id, $event.target.value)"
                                                        class="px-2 py-1.5 border border-slate-205 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs rounded-xl outline-none text-slate-800 dark:text-slate-150">
                                                    <option value="Pending">Pending</option>
                                                    <option value="In Progress">In Progress</option>
                                                    <option value="Completed">Completed</option>
                                                </select>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-xs">
                                                <button @click="deleteActionItem(item.id)" class="font-bold text-rose-600 hover:text-rose-800 dark:text-red-400 dark:hover:text-red-300 px-2 py-1">
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="getActionItems().length === 0">
                                        <td colspan="6" class="px-6 py-8 text-center text-slate-400 italic">No action items created yet. Complete meetings to sync items from Fireflies.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Sub-tab 5: Decisions -->
                <div x-show="meetingsSubTab === 'decisions'" class="space-y-4">
                    <div class="flex items-center justify-between pb-2">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Logged Team Agreements & Decisions</span>
                        <div class="flex items-center gap-2">
                            <select x-model="addDecisionMeetingId" class="px-2 py-1.5 border border-slate-205 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs rounded-xl outline-none text-slate-800 dark:text-slate-150">
                                <option value="">Select Meeting to Log Decision...</option>
                                <template x-for="meeting in meetings" :key="meeting.id">
                                    <option :value="meeting.id" x-text="meeting.title"></option>
                                </template>
                            </select>
                            <button @click="if (addDecisionMeetingId) { openAddDecision(addDecisionMeetingId) } else { alert('Please select a meeting first') }" 
                                    class="px-2.5 py-1.5 bg-skyAccent hover:bg-sky-650 text-white rounded-lg text-xs font-bold shadow">
                                + Log Decision
                            </button>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-800/40 text-slate-500 dark:text-slate-400 text-xs font-bold uppercase border-b border-slate-200 dark:border-slate-800">
                                        <th class="px-6 py-4">Logged Decision</th>
                                        <th class="px-6 py-4">Associated Meeting</th>
                                        <th class="px-6 py-4">Meeting Date</th>
                                        <th class="px-6 py-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                                    <template x-for="item in getDecisions().filter(d => meetingsSearchQuery === '' || d.decision_text.toLowerCase().includes(meetingsSearchQuery.toLowerCase()) || d.meeting_title.toLowerCase().includes(meetingsSearchQuery.toLowerCase()))" :key="item.id">
                                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                                            <td class="px-6 py-4">
                                                <span class="font-semibold text-slate-900 dark:text-white" x-text="item.decision_text"></span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs">
                                                <a :href="`/dashboard/meetings/${item.meeting_id}`" class="underline text-skyAccent dark:text-blue-400 font-bold" x-text="item.meeting_title"></a>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-550" x-text="formatDate(item.date)"></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-xs">
                                                <button @click="deleteDecision(item.id)" class="font-bold text-rose-600 hover:text-rose-800 dark:text-red-400 dark:hover:text-red-300 px-2 py-1">
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="getDecisions().length === 0">
                                        <td colspan="4" class="px-6 py-8 text-center text-slate-400 italic">No decisions logged yet. Complete meetings to sync decisions from Fireflies.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 1: SCHEDULE MEETING -->
    <div x-cloak x-show="showMeetingModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div @click="showMeetingModal = false" class="absolute inset-0 bg-slate-950/40"></div>
        <!-- Content -->
        <div class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 w-full max-w-md shadow-2xl animate-scale-in">
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">Schedule Team Meeting</h3>
            
            <form @submit.prevent="scheduleMeeting()" class="space-y-4">
                <div class="space-y-1">
                    <label for="meeting_title" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Meeting Title</label>
                    <input type="text" x-model="newMeeting.title" id="meeting_title" required placeholder="e.g. Sync & Sprint Review"
                           class="w-full px-4 py-2 border border-slate-200 dark:border-slate-800 bg-transparent text-sm rounded-xl outline-none text-slate-800 dark:text-slate-100">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label for="meeting_date" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Meeting Date</label>
                        <input type="date" x-model="newMeeting.meeting_date" id="meeting_date" required
                               class="w-full px-4 py-2 border border-slate-200 dark:border-slate-800 bg-transparent text-sm rounded-xl outline-none text-slate-800 dark:text-slate-100">
                    </div>
                    <div class="space-y-1">
                        <label for="meeting_time" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Meeting Time</label>
                        <input type="time" x-model="newMeeting.meeting_time" id="meeting_time" required
                               class="w-full px-4 py-2 border border-slate-200 dark:border-slate-800 bg-transparent text-sm rounded-xl outline-none text-slate-800 dark:text-slate-100">
                    </div>
                </div>

                <div class="space-y-1">
                    <label for="meeting_duration" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Duration (Minutes)</label>
                    <input type="number" x-model="newMeeting.duration" id="meeting_duration" required min="5" placeholder="30"
                           class="w-full px-4 py-2 border border-slate-200 dark:border-slate-800 bg-transparent text-sm rounded-xl outline-none text-slate-800 dark:text-slate-100">
                </div>

                <div class="space-y-1">
                    <label for="meeting_link" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Meeting Link</label>
                    <input type="url" x-model="newMeeting.meeting_link" id="meeting_link" placeholder="https://zoom.us/j/..."
                           class="w-full px-4 py-2 border border-slate-200 dark:border-slate-800 bg-transparent text-sm rounded-xl outline-none text-slate-805 dark:text-slate-100">
                </div>
                
                <div class="space-y-1">
                    <label for="meeting_desc" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Agenda / Description</label>
                    <textarea x-model="newMeeting.description" id="meeting_desc" rows="3" placeholder="Briefly specify topics to address..."
                              class="w-full px-4 py-2 border border-slate-200 dark:border-slate-800 bg-transparent text-sm rounded-xl outline-none text-slate-850 dark:text-slate-200"></textarea>
                </div>

                <!-- Invitees Selection -->
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Select Participants</label>
                    <div class="max-h-24 overflow-y-auto border border-slate-200 dark:border-slate-800 p-2.5 rounded-xl space-y-1.5">
                        <template x-for="m in teamMembers" :key="m.id">
                            <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-350">
                                <input type="checkbox" :value="m.id" x-model="newMeeting.participants" class="rounded border-slate-300 text-skyAccent dark:bg-slate-900 dark:border-slate-850">
                                <span x-text="m.name"></span>
                            </label>
                        </template>
                    </div>
                </div>
                
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showMeetingModal = false"
                            class="px-4 py-2 rounded-xl text-xs font-semibold border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-350">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-xl text-xs font-bold bg-skyAccent hover:bg-sky-650 text-white shadow-sm">
                        Schedule Meeting
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- RESCHEDULE MEETING MODAL -->
    <div x-cloak x-show="showRescheduleModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="showRescheduleModal = false" class="absolute inset-0 bg-slate-950/40"></div>
        <div class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 w-full max-w-md shadow-2xl animate-scale-in">
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">Reschedule Team Meeting</h3>
            <form @submit.prevent="confirmReschedule()" class="space-y-4">
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">New Date</label>
                    <input type="date" x-model="rescheduleDate" required
                           class="w-full px-4 py-2 border border-slate-200 dark:border-slate-800 bg-transparent text-sm rounded-xl outline-none text-slate-800 dark:text-slate-100">
                </div>
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">New Time</label>
                    <input type="time" x-model="rescheduleTime" required
                           class="w-full px-4 py-2 border border-slate-200 dark:border-slate-800 bg-transparent text-sm rounded-xl outline-none text-slate-800 dark:text-slate-100">
                </div>
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showRescheduleModal = false"
                            class="px-4 py-2 rounded-xl text-xs font-semibold border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-350">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-xl text-xs font-bold bg-skyAccent hover:bg-sky-650 text-white shadow-sm">
                        Reschedule Meeting
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ADD ACTION ITEM MODAL -->
    <div x-cloak x-show="showAddActionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="showAddActionModal = false" class="absolute inset-0 bg-slate-950/40"></div>
        <div class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 w-full max-w-md shadow-2xl animate-scale-in">
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">Add Action Item Task</h3>
            <form @submit.prevent="saveActionItem()" class="space-y-4">
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Action Task Text</label>
                    <textarea x-model="newAction.action_item" required rows="3" placeholder="Describe the deliverable..."
                              class="w-full px-4 py-2 border border-slate-200 dark:border-slate-800 bg-transparent text-sm rounded-xl outline-none text-slate-800 dark:text-slate-150"></textarea>
                </div>
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Assign Employee</label>
                    <select x-model="newAction.assigned_to" class="w-full px-4 py-2 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-sm rounded-xl outline-none text-slate-800 dark:text-slate-100">
                        <option value="">Leave Unassigned</option>
                        <template x-for="m in teamMembers" :key="m.id">
                            <option :value="m.id" x-text="m.name"></option>
                        </template>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Due Date</label>
                        <input type="date" x-model="newAction.due_date"
                               class="w-full px-4 py-2 border border-slate-200 dark:border-slate-800 bg-transparent text-sm rounded-xl outline-none text-slate-800 dark:text-slate-100">
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Priority</label>
                        <select x-model="newAction.priority" class="w-full px-4 py-2 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-sm rounded-xl outline-none text-slate-800 dark:text-slate-100">
                            <option value="High">High</option>
                            <option value="Medium">Medium</option>
                            <option value="Low">Low</option>
                        </select>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showAddActionModal = false"
                            class="px-4 py-2 rounded-xl text-xs font-semibold border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-350">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-xl text-xs font-bold bg-skyAccent hover:bg-sky-650 text-white shadow-sm">
                        Create Action Task
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ADD DECISION MODAL -->
    <div x-cloak x-show="showAddDecisionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="showAddDecisionModal = false" class="absolute inset-0 bg-slate-950/40"></div>
        <div class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 w-full max-w-md shadow-2xl animate-scale-in">
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">Log Decision</h3>
            <form @submit.prevent="saveDecision()" class="space-y-4">
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Decision Text</label>
                    <textarea x-model="newDecisionText" required rows="4" placeholder="e.g. Approved the new project roadmap design..."
                              class="w-full px-4 py-2 border border-slate-200 dark:border-slate-800 bg-transparent text-sm rounded-xl outline-none text-slate-800 dark:text-slate-150"></textarea>
                </div>
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showAddDecisionModal = false"
                            class="px-4 py-2 rounded-xl text-xs font-semibold border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-350">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-xl text-xs font-bold bg-skyAccent hover:bg-sky-650 text-white shadow-sm">
                        Log Decision
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: ASSIGN TASK TO TEAM -->
    <div x-cloak x-show="showTaskModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div @click="showTaskModal = false" class="absolute inset-0 bg-slate-950/40"></div>
        <!-- Content -->
        <div class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 w-full max-w-md shadow-2xl animate-scale-in">
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">Assign Task to Team</h3>
            
            <form action="{{ route('dashboard.teams.tasks.store', $team->id) }}" method="POST" class="space-y-4">
                @csrf
                <div class="space-y-1">
                    <label for="task_title" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Task Title</label>
                    <input type="text" name="title" id="task_title" required placeholder="e.g. Integrate Payment Gateway"
                           class="w-full px-4 py-2 border border-slate-200 dark:border-slate-800 bg-transparent text-sm rounded-xl outline-none text-slate-800 dark:text-slate-100">
                </div>

                <div class="space-y-1">
                    <label for="assigned_to" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Assign to Member (Optional)</label>
                    <select name="assigned_to" id="assigned_to"
                            class="w-full px-4 py-2 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-sm rounded-xl outline-none text-slate-800 dark:text-slate-100">
                        <option value="">Leave Unassigned (Team Pool)</option>
                        @foreach($team->members as $member)
                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="space-y-1">
                    <label for="task_deadline" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Deadline</label>
                    <input type="date" name="deadline" id="task_deadline"
                           class="w-full px-4 py-2 border border-slate-200 dark:border-slate-800 bg-transparent text-sm rounded-xl outline-none text-slate-850 dark:text-slate-100">
                </div>
                
                <div class="space-y-1">
                    <label for="task_desc" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Description</label>
                    <textarea name="description" id="task_desc" rows="3" placeholder="Briefly specify details of deliverables..."
                              class="w-full px-4 py-2 border border-slate-200 dark:border-slate-800 bg-transparent text-sm rounded-xl outline-none text-slate-850 dark:text-slate-200"></textarea>
                </div>
                
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showTaskModal = false"
                            class="px-4 py-2 rounded-xl text-xs font-semibold border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-350">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-xl text-xs font-bold bg-skyAccent hover:bg-sky-650 text-white shadow-sm">
                        Confirm & Assign Task
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function meetingDashboardState() {
    return {
        activeTab: new URLSearchParams(window.location.search).get('tab') || 'overview',
        showMeetingModal: false,
        showTaskModal: false,
        
        // Meetings Sub-navigation
        meetingsSubTab: 'upcoming',
        meetingsSearchQuery: '',
        historyFilter: 'all',
        customStartDate: '',
        customEndDate: '',
        actionStatusFilter: 'all',
        actionPriorityFilter: 'all',
        
        // Meetings List (loaded dynamically from PHP)
        meetings: {!! json_encode($meetings->map(fn($m) => [
            'id' => $m->id,
            'title' => $m->title,
            'description' => $m->description,
            'meeting_date' => $m->meeting_date ? $m->meeting_date->toDateString() : null,
            'meeting_time' => $m->meeting_time,
            'duration' => $m->duration,
            'meeting_link' => $m->meeting_link,
            'status' => $m->status,
            'created_by' => $m->created_by,
            'creator_name' => $m->creator ? $m->creator->name : 'Manager',
            'participants' => $m->participants ?? [],
            'transcript' => $m->transcript ? [
                'id' => $m->transcript->id,
                'transcript' => $m->transcript->transcript,
                'summary' => $m->transcript->summary,
                'sentiment' => $m->transcript->sentiment,
            ] : null,
            'action_items' => $m->actionItems->map(fn($item) => [
                'id' => $item->id,
                'action_item' => $item->action_item,
                'assigned_to' => $item->assigned_to,
                'assignee_name' => $item->assignee ? $item->assignee->name : 'Unassigned',
                'due_date' => $item->due_date ? $item->due_date->toDateString() : null,
                'priority' => $item->priority,
                'status' => $item->status,
            ])->toArray(),
            'decisions' => $m->decisions->map(fn($d) => [
                'id' => $d->id,
                'decision_text' => $d->decision_text,
            ])->toArray(),
        ])) !!},

        teamMembers: {!! json_encode($team->members->map(fn($m) => ['id' => $m->id, 'name' => $m->name])) !!},

        // Scheduling meeting form state
        newMeeting: {
            title: '',
            description: '',
            meeting_date: '',
            meeting_time: '',
            duration: 30,
            meeting_link: '',
            participants: []
        },

        // Rescheduling state
        showRescheduleModal: false,
        rescheduleMeetingId: null,
        rescheduleDate: '',
        rescheduleTime: '',

        // Action item creation
        showAddActionModal: false,
        addActionMeetingId: null,
        newAction: {
            action_item: '',
            assigned_to: '',
            due_date: '',
            priority: 'Medium'
        },

        // Decision creation
        showAddDecisionModal: false,
        addDecisionMeetingId: null,
        newDecisionText: '',

        // AJAX operations
        scheduleMeeting() {
            fetch(`/dashboard/teams/{{ $team->id }}/meetings`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(this.newMeeting)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success || data.meeting) {
                    let meeting = data.meeting;
                    meeting.creator_name = '{{ auth()->user()->name }}';
                    meeting.transcript = null;
                    meeting.action_items = [];
                    meeting.decisions = [];
                    this.meetings.unshift(meeting);
                    this.showMeetingModal = false;
                    this.newMeeting = { title: '', description: '', meeting_date: '', meeting_time: '', duration: 30, meeting_link: '', participants: [] };
                }
            });
        },

        openReschedule(meeting) {
            this.rescheduleMeetingId = meeting.id;
            this.rescheduleDate = meeting.meeting_date;
            this.rescheduleTime = meeting.meeting_time;
            this.showRescheduleModal = true;
        },

        confirmReschedule() {
            fetch(`/dashboard/meetings/${this.rescheduleMeetingId}/reschedule`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    meeting_date: this.rescheduleDate,
                    meeting_time: this.rescheduleTime
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    let index = this.meetings.findIndex(m => m.id === this.rescheduleMeetingId);
                    if (index !== -1) {
                        this.meetings[index].meeting_date = data.meeting.meeting_date;
                        this.meetings[index].meeting_time = data.meeting.meeting_time;
                        this.meetings[index].status = data.meeting.status;
                    }
                    this.showRescheduleModal = false;
                }
            });
        },

        cancelMeeting(meetingId) {
            if (!confirm('Are you sure you want to cancel this meeting?')) return;
            fetch(`/dashboard/meetings/${meetingId}/cancel`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    let index = this.meetings.findIndex(m => m.id === meetingId);
                    if (index !== -1) {
                        this.meetings[index].status = 'Cancelled';
                    }
                }
            });
        },

        completeMeeting(meetingId) {
            fetch(`/dashboard/meetings/${meetingId}/complete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    let index = this.meetings.findIndex(m => m.id === meetingId);
                    if (index !== -1) {
                        this.meetings[index] = {
                            ...this.meetings[index],
                            status: data.meeting.status,
                            transcript: data.meeting.transcript,
                            action_items: data.meeting.action_items,
                            decisions: data.meeting.decisions
                        };
                    }
                }
            });
        },

        syncFireflies(meetingId) {
            fetch(`/dashboard/meetings/${meetingId}/sync-fireflies`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    let index = this.meetings.findIndex(m => m.id === meetingId);
                    if (index !== -1) {
                        this.meetings[index] = {
                            ...this.meetings[index],
                            transcript: data.meeting.transcript,
                            action_items: data.meeting.action_items,
                            decisions: data.meeting.decisions
                        };
                    }
                }
            });
        },

        // Action Items management
        updateActionStatus(actionId, status) {
            fetch(`/dashboard/action-items/${actionId}/update`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.meetings.forEach(m => {
                        let actionIndex = m.action_items.findIndex(item => item.id === actionId);
                        if (actionIndex !== -1) {
                            m.action_items[actionIndex].status = data.action_item.status;
                        }
                    });
                }
            });
        },

        updateActionAssignee(actionId, assignedTo) {
            fetch(`/dashboard/action-items/${actionId}/update`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ 
                    status: 'Pending',
                    assigned_to: assignedTo 
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.meetings.forEach(m => {
                        let actionIndex = m.action_items.findIndex(item => item.id === actionId);
                        if (actionIndex !== -1) {
                            m.action_items[actionIndex].assigned_to = data.action_item.assigned_to;
                            m.action_items[actionIndex].assignee_name = data.action_item.assignee_name;
                        }
                    });
                }
            });
        },

        deleteActionItem(actionId) {
            if (!confirm('Are you sure you want to delete this action item?')) return;
            fetch(`/dashboard/action-items/${actionId}/delete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.meetings.forEach(m => {
                        m.action_items = m.action_items.filter(item => item.id !== actionId);
                    });
                }
            });
        },

        openAddAction(meetingId) {
            this.addActionMeetingId = meetingId;
            this.newAction = { action_item: '', assigned_to: '', due_date: '', priority: 'Medium' };
            this.showAddActionModal = true;
        },

        saveActionItem() {
            fetch(`/dashboard/action-items`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    meeting_id: this.addActionMeetingId,
                    ...this.newAction
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    let index = this.meetings.findIndex(m => m.id === this.addActionMeetingId);
                    if (index !== -1) {
                        this.meetings[index].action_items.push({
                            id: data.action_item.id,
                            action_item: data.action_item.action_item,
                            assigned_to: data.action_item.assigned_to,
                            assignee_name: data.action_item.assignee_name,
                            due_date: data.action_item.due_date,
                            priority: data.action_item.priority,
                            status: data.action_item.status
                        });
                    }
                    this.showAddActionModal = false;
                }
            });
        },

        // Decisions management
        openAddDecision(meetingId) {
            this.addDecisionMeetingId = meetingId;
            this.newDecisionText = '';
            this.showAddDecisionModal = true;
        },

        saveDecision() {
            fetch(`/dashboard/decisions`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    meeting_id: this.addDecisionMeetingId,
                    decision_text: this.newDecisionText
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    let index = this.meetings.findIndex(m => m.id === this.addDecisionMeetingId);
                    if (index !== -1) {
                        this.meetings[index].decisions.push({
                            id: data.decision.id,
                            decision_text: data.decision.decision_text
                        });
                    }
                    this.showAddDecisionModal = false;
                }
            });
        },

        deleteDecision(decisionId) {
            if (!confirm('Are you sure you want to delete this decision?')) return;
            fetch(`/dashboard/decisions/${decisionId}/delete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.meetings.forEach(m => {
                        m.decisions = m.decisions.filter(d => d.id !== decisionId);
                    });
                }
            });
        },

        getActionItems() {
            let list = [];
            this.meetings.forEach(m => {
                if (m.action_items) {
                    m.action_items.forEach(item => {
                        list.push({ ...item, meeting_title: m.title, meeting_id: m.id });
                    });
                }
            });
            return list;
        },

        getDecisions() {
            let list = [];
            this.meetings.forEach(m => {
                if (m.decisions) {
                    m.decisions.forEach(d => {
                        list.push({ ...d, meeting_title: m.title, meeting_id: m.id, date: m.meeting_date });
                    });
                }
            });
            return list;
        },

        formatDate(dateStr) {
            if (!dateStr) return 'TBD';
            const d = new Date(dateStr);
            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', timeZone: 'UTC' });
        },

        formatTime(timeStr) {
            if (!timeStr) return 'TBD';
            const parts = timeStr.split(':');
            let hours = parseInt(parts[0]);
            const minutes = parts[1];
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12;
            return `${hours}:${minutes} ${ampm}`;
        }
    };
}
</script>
@endsection
