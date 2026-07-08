@extends('layouts.app')

@section('content')
<div class="space-y-8 animate-fade-in">
    <!-- Header -->
    <div>
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Global Search Results</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Showing matches for "<span class="font-semibold text-slate-800 dark:text-slate-200">{{ $query }}</span>"
        </p>
    </div>

    @php
        $totalResults = $employees->count() + $teams->count() + $projects->count() + $tasks->count();
    @endphp

    @if ($totalResults === 0)
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-12 text-center">
            <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center mx-auto mb-4 text-slate-400">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-slate-800 dark:text-slate-200">No results found</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-md mx-auto">
                We couldn't find anything matching your search term. Check your spelling or try typing a different query.
            </p>
        </div>
    @else
        <!-- Results Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Employees Matches -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                    <svg class="w-5 h-5 text-skyAccent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    Employees ({{ $employees->count() }})
                </h3>
                @forelse ($employees as $employee)
                    <div class="flex items-center justify-between p-3 rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-sky-50 dark:bg-blue-950/20 text-skyAccent dark:text-blue-400 flex items-center justify-center font-bold text-sm">
                                {{ substr($employee->name, 0, 2) }}
                            </div>
                            <div>
                                <span class="block font-bold text-slate-800 dark:text-slate-200">{{ $employee->name }}</span>
                                <span class="block text-xs text-slate-500">{{ $employee->email }}</span>
                            </div>
                        </div>
                        <a href="{{ route('dashboard.employees.show', $employee->id) }}" class="text-xs font-bold text-skyAccent hover:text-sky-650 dark:text-blue-400 dark:hover:text-blue-300">
                            View Portal
                        </a>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 italic p-2">No employee matches.</p>
                @endforelse
            </div>

            <!-- Teams Matches -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                    <svg class="w-5 h-5 text-skyAccent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    Teams ({{ $teams->count() }})
                </h3>
                @forelse ($teams as $team)
                    <div class="flex items-center justify-between p-3 rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-sky-50 dark:bg-blue-950/20 text-skyAccent dark:text-blue-400 flex items-center justify-center font-bold text-sm">
                                {{ substr($team->name, 0, 1) }}
                            </div>
                            <div>
                                <span class="block font-bold text-slate-800 dark:text-slate-200">{{ $team->name }}</span>
                                <span class="block text-xs text-slate-500">{{ $team->members_count }} members</span>
                            </div>
                        </div>
                        <a href="{{ route('dashboard.teams.show', $team->id) }}" class="text-xs font-bold text-skyAccent hover:text-sky-650 dark:text-blue-400 dark:hover:text-blue-300">
                            View Dashboard
                        </a>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 italic p-2">No team matches.</p>
                @endforelse
            </div>

            <!-- Projects Matches -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                    <svg class="w-5 h-5 text-skyAccent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                    </svg>
                    Projects ({{ $projects->count() }})
                </h3>
                @forelse ($projects as $project)
                    <div class="flex items-start justify-between p-3 rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-lg bg-sky-50 dark:bg-blue-950/20 text-skyAccent dark:text-blue-400 flex items-center justify-center font-bold text-sm shrink-0 mt-0.5">
                                {{ substr($project->name, 0, 1) }}
                            </div>
                            <div class="max-w-[250px] sm:max-w-md">
                                <span class="block font-bold text-slate-800 dark:text-slate-200 truncate">{{ $project->name }}</span>
                                <span class="block text-xs text-slate-500 line-clamp-2">{{ $project->description ?? 'No description.' }}</span>
                            </div>
                        </div>
                        <a href="{{ route('dashboard.projects.show', $project->id) }}" class="text-xs font-bold text-skyAccent hover:text-sky-650 dark:text-blue-400 dark:hover:text-blue-300 shrink-0">
                            Open Workspace
                        </a>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 italic p-2">No project matches.</p>
                @endforelse
            </div>

            <!-- Tasks Matches -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                    <svg class="w-5 h-5 text-skyAccent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                    </svg>
                    Tasks ({{ $tasks->count() }})
                </h3>
                @forelse ($tasks as $task)
                    <div class="flex items-start justify-between p-3 rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                        <div>
                            <span class="block font-bold text-slate-800 dark:text-slate-200">{{ $task->title }}</span>
                            <span class="block text-xs text-slate-400">Assigned to: {{ $task->assignee->name ?? 'Unassigned' }}</span>
                            <div class="mt-1 flex items-center gap-2">
                                @if ($task->status === 'completed')
                                    <span class="inline-flex px-1.5 py-0.5 rounded bg-green-50 dark:bg-green-950/20 text-green-700 dark:text-green-400 text-[10px] font-semibold">Completed</span>
                                @elseif ($task->status === 'in_progress')
                                    <span class="inline-flex px-1.5 py-0.5 rounded bg-sky-50 dark:bg-sky-950/20 text-skyAccent dark:text-sky-400 text-[10px] font-semibold">In Progress</span>
                                @else
                                    <span class="inline-flex px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-[10px] font-semibold">Pending</span>
                                @endif
                                @if ($task->deadline)
                                    <span class="text-[10px] text-slate-450">Due: {{ $task->deadline->format('M d, Y') }}</span>
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('dashboard.tasks.index') }}" class="text-xs font-bold text-skyAccent hover:text-sky-650 dark:text-blue-400 dark:hover:text-blue-300">
                            Go to Board
                        </a>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 italic p-2">No task matches.</p>
                @endforelse
            </div>

        </div>
    @endif
</div>
@endsection
