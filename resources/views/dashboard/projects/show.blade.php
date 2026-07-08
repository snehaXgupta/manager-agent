@extends('layouts.app')

@section('content')
<div class="space-y-8 animate-fade-in" x-data="{ showTaskModal: false }">
    <!-- Header / Back Nav -->
    <div class="space-y-4">
        <a href="{{ route('dashboard.projects.index') }}" 
           class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-skyAccent dark:text-slate-400 dark:hover:text-blue-400 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Projects
        </a>
        
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-sky-50 dark:bg-blue-950/20 text-skyAccent dark:text-blue-400 flex items-center justify-center font-bold text-lg border border-slate-200 dark:border-slate-800">
                    {{ substr($project->name, 0, 2) }}
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $project->name }}</h2>
                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                            {{ $project->category ?: 'Development' }}
                        </span>
                        @if($project->status === 'completed')
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-green-50 dark:bg-green-950/20 text-green-700 dark:text-green-400 border border-green-200/20">Completed</span>
                        @elseif($project->status === 'on_hold')
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-400 border border-amber-200/20">On Hold</span>
                        @else
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-sky-50 dark:bg-sky-950/20 text-skyAccent dark:text-sky-400 border border-sky-200/20">Active</span>
                        @endif
                    </div>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mt-1.5 font-medium">
                        <span>{{ $project->description ?? 'No description provided.' }}</span>
                        @if($project->deadline)
                            <span class="hidden sm:inline text-slate-300 dark:text-slate-700">|</span>
                            <span class="flex items-center gap-1 font-bold {{ \Carbon\Carbon::parse($project->deadline)->isPast() && $project->status !== 'completed' ? 'text-red-500' : 'text-slate-600 dark:text-slate-300' }}">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                Target Deadline: {{ \Carbon\Carbon::parse($project->deadline)->format('M d, Y') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <button @click="showTaskModal = true" 
                        class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold bg-skyAccent hover:bg-sky-650 text-white shadow-sm transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Assign Task to Project
                </button>
            </div>
        </div>
    </div>

    <!-- Main Workspace Layout (Two Columns) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left Side: Metrics & Members -->
        <div class="space-y-8 lg:col-span-1">
            
            <!-- Project Health & Metrics Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-6">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3 flex items-center justify-between">
                    <span>Project Health Summary</span>
                    @php
                        $score = $projectMetrics['health_score'];
                        $colorClass = $score >= 80 ? 'text-green-500 bg-green-50 dark:bg-green-950/20' : ($score >= 50 ? 'text-amber-500 bg-amber-50 dark:bg-amber-950/20' : 'text-red-500 bg-red-50 dark:bg-red-950/20');
                    @endphp
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $colorClass }}">
                        {{ $score >= 80 ? 'Good' : ($score >= 50 ? 'Warning' : 'At Risk') }}
                    </span>
                </h3>
                
                <div class="flex flex-col items-center justify-center py-2 relative">
                    <!-- Radial SVG Gauge -->
                    <div class="relative w-36 h-36">
                        <svg class="w-full h-full transform -rotate-90" viewBox="0 0 120 120">
                            <!-- Background Circle -->
                            <circle cx="60" cy="60" r="50" class="stroke-slate-100 dark:stroke-slate-800 fill-none" stroke-width="8" />
                            <!-- Foreground Progress Circle -->
                            @php
                                $circumference = 314.16;
                                $dashoffset = $circumference - ($score / 100) * $circumference;
                                $strokeColor = $score >= 80 ? '#10b981' : ($score >= 50 ? '#f59e0b' : '#ef4444');
                            @endphp
                            <circle cx="60" cy="60" r="50" class="fill-none transition-all duration-1000 ease-out" 
                                    stroke="{{ $strokeColor }}" stroke-width="8" stroke-linecap="round"
                                    stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $dashoffset }}" />
                        </svg>
                        <!-- Central Text -->
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-3xl font-extrabold text-slate-900 dark:text-white">{{ $score }}</span>
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Health</span>
                        </div>
                    </div>

                    <!-- Health Warnings / Alerts -->
                    @if(!empty($projectMetrics['health_warnings']))
                        <div class="w-full mt-4 space-y-2">
                            @foreach($projectMetrics['health_warnings'] as $warning)
                                <div class="flex items-start gap-2 p-2.5 bg-red-50/50 dark:bg-red-950/10 border border-red-100 dark:border-red-900/20 rounded-xl text-xs text-red-650 dark:text-red-400">
                                    <svg class="w-4 h-4 shrink-0 mt-0.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    <span>{{ $warning }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="w-full mt-4 p-2.5 bg-green-50/50 dark:bg-green-950/10 border border-green-100 dark:border-green-900/20 rounded-xl text-xs text-green-650 dark:text-green-400 flex items-center gap-2">
                            <svg class="w-4 h-4 shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>Optimal performance factors.</span>
                        </div>
                    @endif
                </div>

                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-4">
                    <!-- Task Completion Rate -->
                    <div class="space-y-2">
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-slate-500 font-medium">Task Completion Rate</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ $projectMetrics['task_completion_rate'] }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2">
                            <div class="bg-skyAccent h-2 rounded-full transition-all duration-500" style="width: {{ $projectMetrics['task_completion_rate'] }}%"></div>
                        </div>
                    </div>

                    <!-- Deadline Adherence Rate -->
                    <div class="space-y-2">
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-slate-500 font-medium">Deadline Adherence</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ $projectMetrics['deadline_adherence_rate'] }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2">
                            <div class="bg-emerald-500 h-2 rounded-full transition-all duration-500" style="width: {{ $projectMetrics['deadline_adherence_rate'] }}%"></div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-100 dark:border-slate-800/80">
                    <div class="p-3 bg-slate-50 dark:bg-slate-800/30 rounded-xl text-center">
                        <span class="block text-xl font-bold text-slate-900 dark:text-white">{{ $projectMetrics['total_tasks'] }}</span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase">Total Tasks</span>
                    </div>
                    <div class="p-3 bg-slate-50 dark:bg-slate-800/30 rounded-xl text-center">
                        <span class="block text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ $projectMetrics['completed_tasks'] }}</span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase">Completed</span>
                    </div>
                </div>
            </div>

            <!-- GitLab repository integration -->
            @if($project->repository)
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3 flex items-center justify-between">
                        <span>GitLab Repository</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-sky-55 dark:bg-blue-950/20 text-skyAccent dark:text-blue-400 border border-sky-100 dark:border-blue-900/30">
                            {{ $project->repository->visibility }}
                        </span>
                    </h3>
                    <div class="text-xs space-y-2">
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-medium">Name:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ $project->repository->repository_name }}</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-slate-500 font-medium">Clone URL:</span>
                            <span class="font-mono text-[10px] text-slate-650 bg-slate-50 dark:bg-slate-850 p-2 rounded break-all select-all block">
                                {{ $project->repository->repository_url }}
                            </span>
                        </div>
                        <div class="pt-2">
                            <a href="{{ $project->repository->repository_url }}" target="_blank" rel="noopener noreferrer"
                               class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 active:scale-95 transition-all">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                Visit Repository
                            </a>
                        </div>
                    </div>

                    <!-- Sync Action Forms -->
                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800 grid grid-cols-2 gap-2 text-center">
                        <form action="{{ route('dashboard.projects.sync-commits', $project->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:text-skyAccent hover:border-skyAccent font-bold text-[10px] transition-colors">
                                Sync Commits
                            </button>
                        </form>
                        <form action="{{ route('dashboard.projects.sync-mrs', $project->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:text-skyAccent hover:border-skyAccent font-bold text-[10px] transition-colors">
                                Sync MRs
                            </button>
                        </form>
                        <form action="{{ route('dashboard.projects.sync-reviews', $project->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:text-skyAccent hover:border-skyAccent font-bold text-[10px] transition-colors">
                                Sync Reviews
                            </button>
                        </form>
                        <form action="{{ route('dashboard.projects.sync-members', $project->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:text-skyAccent hover:border-skyAccent font-bold text-[10px] transition-colors">
                                Sync Members
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <!-- Project Members Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3 mb-4 flex items-center justify-between">
                    <span>Project Members</span>
                    <span class="px-2 py-0.5 rounded bg-slate-55 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-bold">{{ $project->members->count() }} members</span>
                </h3>
                
                <div class="space-y-3 max-h-96 overflow-y-auto pr-1">
                    @forelse($project->members as $member)
                        <div class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-slate-855/30 hover:bg-slate-100 dark:hover:bg-slate-800/80 transition-colors">
                            <div class="w-8 h-8 rounded-full bg-skyAccent/10 text-skyAccent dark:bg-blue-950/30 dark:text-blue-400 flex items-center justify-center font-bold text-xs shrink-0">
                                {{ substr($member->name, 0, 2) }}
                            </div>
                            <div class="min-w-0">
                                <a href="{{ route('dashboard.employees.show', $member->id) }}" 
                                   class="block text-xs font-bold text-slate-800 hover:text-skyAccent dark:text-slate-200 dark:hover:text-blue-400 transition-colors truncate">
                                    {{ $member->name }}
                                </a>
                                <span class="block text-[10px] text-slate-400 truncate">{{ $member->email }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 text-xs text-slate-450 italic">
                            No members assigned to this project.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- Right Side: Project Tasks Board -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-sm overflow-hidden flex flex-col justify-between h-full">
                <div>
                    <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Project Task Board</h3>
                        <span class="text-xs text-slate-400">Sorted by status & deadline</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/70 dark:bg-slate-800/20 text-slate-500 dark:text-slate-400 text-xs font-bold uppercase border-b border-slate-200 dark:border-slate-800">
                                    <th class="px-6 py-4">Task Details</th>
                                    <th class="px-6 py-4">Assignee</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4">Deadline</th>
                                    <th class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-150 dark:divide-slate-800/50 text-sm">
                                @forelse($tasks as $task)
                                    <tr class="hover:bg-slate-50/30 dark:hover:bg-slate-800/10 transition-colors">
                                        <td class="px-6 py-4">
                                            <span class="block font-semibold text-slate-900 dark:text-white max-w-xs truncate" title="{{ $task->title }}">{{ $task->title }}</span>
                                            @if($task->description)
                                                <span class="block text-xs text-slate-450 dark:text-slate-400 max-w-xs truncate" title="{{ $task->description }}">{{ $task->description }}</span>
                                            @else
                                                <span class="block text-xs text-slate-350 dark:text-slate-500 italic">No description.</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($task->assignee)
                                                <div class="flex items-center gap-2">
                                                    <div class="w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center font-bold text-[10px] text-slate-600 dark:text-slate-300 shrink-0">
                                                        {{ substr($task->assignee->name, 0, 2) }}
                                                    </div>
                                                    <a href="{{ route('dashboard.employees.show', $task->assignee->id) }}" class="text-xs font-medium text-slate-700 hover:text-skyAccent dark:text-slate-300 dark:hover:text-blue-400 transition-colors truncate max-w-[120px]">
                                                        {{ $task->assignee->name }}
                                                    </a>
                                                </div>
                                            @else
                                                <span class="text-xs text-slate-400 italic">Unassigned</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if ($task->status === 'completed')
                                                <span class="inline-flex px-2 py-0.5 rounded-md bg-green-50 dark:bg-green-950/20 text-green-700 dark:text-green-400 text-xs font-semibold">Completed</span>
                                            @elseif ($task->status === 'in_progress')
                                                <span class="inline-flex px-2 py-0.5 rounded-md bg-sky-50 dark:bg-sky-950/20 text-skyAccent dark:text-sky-400 text-xs font-semibold">In Progress</span>
                                            @else
                                                <span class="inline-flex px-2 py-0.5 rounded-md bg-slate-105 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-semibold">Pending</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-xs font-medium text-slate-500">
                                            @if($task->deadline)
                                                <span class="{{ $task->deadline->isPast() && $task->status !== 'completed' ? 'text-red-500 font-bold' : '' }}">
                                                    {{ $task->deadline->format('M d, Y') }}
                                                </span>
                                            @else
                                                <span class="text-slate-400">No Limit</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-xs">
                                            @if ($task->status !== 'completed')
                                                <form action="{{ route('dashboard.tasks.update', $task->id) }}" method="POST" class="inline-block">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="completed">
                                                    <button type="submit" class="font-bold text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 transition-colors">
                                                        Complete
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-slate-400">Archived</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center text-slate-450 italic">
                                            No tasks found under this project. Click "Assign Task to Project" to add one.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination Section -->
                @if($tasks->hasPages())
                    <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                        {{ $tasks->links() }}
                    </div>
                @endif
            </div>
        </div>

    </div>

    <!-- Alpine.js modal for Task Creation -->
    <div x-cloak x-show="showTaskModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div @click="showTaskModal = false" class="absolute inset-0 bg-slate-950/40 backdrop-blur-sm transition-opacity"></div>
        
        <!-- Content -->
        <div class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 w-full max-w-md shadow-2xl animate-scale-in">
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-skyAccent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                </svg>
                Assign Task to Project
            </h3>
            
            <form action="{{ route('dashboard.projects.tasks.store', $project->id) }}" method="POST" class="space-y-4">
                @csrf
                
                <div class="space-y-2">
                    <label for="task_title" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Task Title</label>
                    <input type="text" name="title" id="task_title" required placeholder="e.g. Implement Payment Gateway"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-transparent text-sm focus:ring-1 focus:ring-skyAccent focus:border-skyAccent outline-none text-slate-800 dark:text-slate-100">
                </div>

                <div class="space-y-2">
                    <label for="assigned_to" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Assignee (Project Member)</label>
                    <select name="assigned_to" id="assigned_to"
                            class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-sm rounded-xl outline-none text-slate-850 dark:text-slate-100 focus:ring-1 focus:ring-skyAccent focus:border-skyAccent">
                        <option value="">Leave Unassigned (Project Shared)</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="space-y-2">
                    <label for="task_deadline" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Deadline</label>
                    <input type="date" name="deadline" id="task_deadline"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-transparent text-sm focus:ring-1 focus:ring-skyAccent focus:border-skyAccent outline-none text-slate-800 dark:text-slate-100">
                </div>
                
                <div class="space-y-2">
                    <label for="task_desc" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Description</label>
                    <textarea name="description" id="task_desc" rows="3" placeholder="Define deliverables and expectations..."
                              class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-transparent text-sm focus:ring-1 focus:ring-skyAccent focus:border-skyAccent outline-none text-slate-850 dark:text-slate-200"></textarea>
                </div>
                
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showTaskModal = false"
                            class="px-4 py-2.5 rounded-xl text-sm font-semibold border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-350 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2.5 rounded-xl text-sm font-bold bg-skyAccent hover:bg-sky-650 text-white shadow-sm transition-all">
                        Confirm & Assign Task
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
