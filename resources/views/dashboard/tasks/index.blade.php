@extends('layouts.app')

@section('content')
<div class="space-y-8 animate-fade-in" x-data="{ showCreateForm: false }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Team Tasks Board</h2>
            <!-- <p class="text-sm text-slate-500 dark:text-slate-400">View and update tasks assigned to your direct reports.</p> -->
        </div>
        <button @click="showCreateForm = !showCreateForm" 
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm bg-skyAccent hover:bg-sky-650 text-white shadow-sm hover:shadow transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Assign New Task
        </button>
    </div>

    <!-- Filters & Search Bar -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
        <form action="{{ route('dashboard.tasks.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
            <!-- Search Title/Desc -->
            <div class="flex-1 min-w-[200px] space-y-1">
                <label for="search" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Search Title / Description</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Search tasks..."
                       class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-xs text-slate-850 dark:text-slate-105">
            </div>

            <!-- Status filter -->
            <div class="w-full sm:w-[150px] space-y-1">
                <label for="status" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Task Status</label>
                <select name="status" id="status"
                        class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-xs text-slate-850 dark:text-slate-105">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
            </div>

            <!-- Health/Urgency filter -->
            <div class="w-full sm:w-[150px] space-y-1">
                <label for="health" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Urgency Health</label>
                <select name="health" id="health"
                        class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-xs text-slate-850 dark:text-slate-105">
                    <option value="">All Health</option>
                    <option value="overdue" {{ request('health') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                    <option value="approaching" {{ request('health') === 'approaching' ? 'selected' : '' }}>Approaching (&lt; 48h)</option>
                    <option value="on_track" {{ request('health') === 'on_track' ? 'selected' : '' }}>On Track / Done</option>
                </select>
            </div>

            <!-- Sort By dropdown -->
            <div class="w-full sm:w-[170px] space-y-1">
                <label for="sort_by" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Sort By</label>
                <select name="sort_by" id="sort_by"
                        class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-xs text-slate-850 dark:text-slate-105">
                    <option value="deadline_asc" {{ request('sort_by') === 'deadline_asc' ? 'selected' : '' }}>Deadline: Soonest</option>
                    <option value="deadline_desc" {{ request('sort_by') === 'deadline_desc' ? 'selected' : '' }}>Deadline: Latest</option>
                    <option value="title_asc" {{ request('sort_by') === 'title_asc' ? 'selected' : '' }}>Title: A-Z</option>
                    <option value="status" {{ request('sort_by') === 'status' ? 'selected' : '' }}>Status Order</option>
                </select>
            </div>

            <!-- Filter Buttons -->
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('dashboard.tasks.index') }}"
                   class="px-4 py-2 border border-slate-200 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl transition-all">
                    Reset
                </a>
                <button type="submit"
                        class="px-4 py-2 bg-skyAccent hover:bg-sky-650 text-white text-xs font-bold rounded-xl shadow-sm transition-all whitespace-nowrap">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Assign New Task Form Card (Alpine driven) -->
    <div x-cloak x-show="showCreateForm" x-collapse
         class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 md:p-8 shadow-md">
        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-skyAccent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
            Assign a New Task
        </h3>
        
        <form action="{{ route('dashboard.tasks.store') }}" method="POST" class="space-y-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Task Title & Description -->
                <div class="space-y-4">
                    <!-- Task Title -->
                    <div class="space-y-2">
                        <label for="title" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Task Title</label>
                        <input type="text" id="title" name="title" required placeholder="e.g. API Integration" 
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-250 dark:border-slate-800 bg-transparent text-sm focus:ring-1 focus:ring-skyAccent focus:border-skyAccent outline-none text-slate-800 dark:text-slate-100">
                    </div>

                    <!-- Description -->
                    <div class="space-y-2">
                        <label for="description" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Description</label>
                        <textarea id="description" name="description" rows="3" placeholder="Provide task requirements..." 
                                  class="w-full px-4 py-2.5 rounded-xl border border-slate-250 dark:border-slate-800 bg-transparent text-sm focus:ring-1 focus:ring-skyAccent focus:border-skyAccent outline-none text-slate-800 dark:text-slate-100"></textarea>
                    </div>
                </div>

                <!-- Deadline & Assignee -->
                <div class="space-y-4">
                    <!-- Deadline -->
                    <div class="space-y-2">
                        <label for="deadline" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Deadline Date</label>
                        <input type="date" id="deadline" name="deadline" required 
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-250 dark:border-slate-800 bg-transparent text-sm focus:ring-1 focus:ring-skyAccent focus:border-skyAccent outline-none text-slate-800 dark:text-slate-100">
                    </div>

                    <!-- Assigned To -->
                    <div class="space-y-2">
                        <label for="assigned_to" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Assignee</label>
                        <select id="assigned_to" name="assigned_to" required 
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-250 dark:border-slate-800 bg-transparent text-sm focus:ring-1 focus:ring-skyAccent focus:border-skyAccent outline-none text-slate-800 dark:text-slate-100">
                            <option value="">Select Team Member...</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="showCreateForm = false" 
                        class="px-4 py-2.5 rounded-xl text-sm font-semibold border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2.5 rounded-xl text-sm font-bold bg-skyAccent hover:bg-sky-650 text-white shadow-sm transition-all">
                    Confirm & Assign Task
                </button>
            </div>
        </form>
    </div>

    <!-- Tasks List Table -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/40 text-slate-500 dark:text-slate-400 text-xs font-bold uppercase border-b border-slate-200 dark:border-slate-800">
                        <th class="px-6 py-4">Task Details</th>
                        <th class="px-6 py-4">Assignee</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Deadline</th>
                        <th class="px-6 py-4">Health</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                    @forelse ($tasks as $task)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                            <!-- Details -->
                            <td class="px-6 py-4">
                                <span class="block font-semibold text-slate-900 dark:text-white">{{ $task->title }}</span>
                                <span class="block text-xs text-slate-400 dark:text-slate-500 max-w-[400px] truncate">{{ $task->description ?? 'No description.' }}</span>
                            </td>

                            <!-- Assignee -->
                            <td class="px-6 py-4 whitespace-nowrap text-slate-700 dark:text-slate-300">
                                {{ $task->assignee->name ?? 'Unassigned' }}
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($task->status === 'completed')
                                    <span class="inline-flex px-2 py-0.5 rounded-md bg-green-50 dark:bg-green-950/20 text-green-700 dark:text-green-400 text-xs font-semibold">Completed</span>
                                @elseif ($task->status === 'in_progress')
                                    <span class="inline-flex px-2 py-0.5 rounded-md bg-sky-50 dark:bg-sky-950/20 text-skyAccent dark:text-sky-400 text-xs font-semibold">In Progress</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-semibold">Pending</span>
                                @endif
                            </td>

                            <!-- Deadline -->
                            <td class="px-6 py-4 whitespace-nowrap text-slate-600 dark:text-slate-300">
                                {{ $task->deadline ? $task->deadline->format('M d, Y') : 'N/A' }}
                            </td>

                            <!-- Urgency health badge -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($task->status === 'completed')
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-50 border border-green-200 text-green-700 dark:bg-green-950/20 dark:border-green-800 dark:text-green-400">
                                        Completed
                                    </span>
                                @elseif ($task->deadline)
                                    @if ($task->deadline->isPast())
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-50 border border-red-200 text-red-700 dark:bg-red-950/20 dark:border-red-800 dark:text-red-400">
                                            Overdue
                                        </span>
                                    @elseif ($task->deadline->diffInHours(now()) <= 48)
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold bg-orange-50 border border-orange-200 text-orange-700 dark:bg-orange-950/20 dark:border-orange-800 dark:text-orange-400">
                                            Approaching (< 48h)
                                        </span>
                                    @else
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-50 border border-green-200 text-green-700 dark:bg-green-950/20 dark:border-green-800 dark:text-green-400">
                                            On Track
                                        </span>
                                    @endif
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 whitespace-nowrap text-right text-xs">
                                @if ($task->status !== 'completed')
                                    <div class="flex items-center justify-end gap-2">
                                        @if ($task->status === 'pending')
                                            <form action="{{ route('dashboard.tasks.update', $task->id) }}" method="POST" class="inline-block">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="in_progress">
                                                <button type="submit" class="text-skyAccent hover:text-sky-700 dark:text-blue-400 dark:hover:text-blue-300 font-semibold">
                                                    Start Work
                                                </button>
                                            </form>
                                            <span class="text-slate-300 dark:text-slate-700">|</span>
                                        @endif
                                        <form action="{{ route('dashboard.tasks.update', $task->id) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="completed">
                                            <button type="submit" class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 font-semibold">
                                                Complete
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-slate-400">Archived</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">
                                No tasks assignees on file.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($tasks->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/40">
                {{ $tasks->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
