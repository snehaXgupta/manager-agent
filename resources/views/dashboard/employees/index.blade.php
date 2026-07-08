@extends('layouts.app')

@section('content')
<div class="space-y-6 animate-fade-in">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Team Employees Management</h2>
            <!-- <p class="text-sm text-slate-500 dark:text-slate-400">Monitor daily attendance, task activity, and active timers for your team.</p> -->
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
        <form action="{{ route('dashboard.employees.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
            <!-- Search field -->
            <div class="flex-1 min-w-[200px] space-y-1">
                <label for="search" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Search Name/Email</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Search employees..."
                       class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-xs text-slate-850 dark:text-slate-105">
            </div>

            <!-- Attendance filter -->
            <div class="w-full sm:w-[150px] space-y-1">
                <label for="status" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Attendance Status</label>
                <select name="status" id="status"
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-xs text-slate-850 dark:text-slate-105">
                    <option value="">All Statuses</option>
                    <option value="present" {{ request('status') === 'present' ? 'selected' : '' }}>Present</option>
                    <option value="late" {{ request('status') === 'late' ? 'selected' : '' }}>Late Arrival</option>
                    <option value="absent" {{ request('status') === 'absent' ? 'selected' : '' }}>Absent</option>
                </select>
            </div>

            <!-- Timer filter -->
            <div class="w-full sm:w-[150px] space-y-1">
                <label for="timer" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Timer Status</label>
                <select name="timer" id="timer"
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-xs text-slate-850 dark:text-slate-105">
                    <option value="">All Timers</option>
                    <option value="active" {{ request('timer') === 'active' ? 'selected' : '' }}>Active Timer</option>
                    <option value="idle" {{ request('timer') === 'idle' ? 'selected' : '' }}>Idle</option>
                </select>
            </div>

            <!-- Department filter -->
            <div class="w-full sm:w-[150px] space-y-1">
                <label for="department_id" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Department</label>
                <select name="department_id" id="department_id"
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-xs text-slate-850 dark:text-slate-105">
                    <option value="">All Departments</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Designation filter -->
            <div class="w-full sm:w-[150px] space-y-1">
                <label for="designation_id" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Designation</label>
                <select name="designation_id" id="designation_id"
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-xs text-slate-850 dark:text-slate-105">
                    <option value="">All Designations</option>
                    @foreach ($designations as $desig)
                        <option value="{{ $desig->id }}" {{ request('designation_id') == $desig->id ? 'selected' : '' }}>{{ $desig->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Sort By dropdown -->
            <div class="w-full sm:w-[170px] space-y-1">
                <label for="sort_by" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Sort By</label>
                <select name="sort_by" id="sort_by"
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-xs text-slate-850 dark:text-slate-105">
                    <option value="name_asc" {{ request('sort_by') === 'name_asc' ? 'selected' : '' }}>Name: A-Z</option>
                    <option value="name_desc" {{ request('sort_by') === 'name_desc' ? 'selected' : '' }}>Name: Z-A</option>
                    <option value="hours_desc" {{ request('sort_by') === 'hours_desc' ? 'selected' : '' }}>Hours worked: High to Low</option>
                    <option value="tasks_desc" {{ request('sort_by') === 'tasks_desc' ? 'selected' : '' }}>Tasks completed: High to Low</option>
                </select>
            </div>

            <!-- Filter Buttons -->
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('dashboard.employees.index') }}"
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

    <!-- Employees List Table/Grid -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/40 text-slate-500 dark:text-slate-400 text-xs font-bold uppercase border-b border-slate-200 dark:border-slate-800">
                        <th class="px-6 py-4">Employee</th>
                        <th class="px-6 py-4">Dept / Desig</th>
                        <th class="px-6 py-4">Attendance Today</th>
                        <th class="px-6 py-4">Active Timer</th>
                        <th class="px-6 py-4 text-center">Hours Worked Today</th>
                        <th class="px-6 py-4 text-center">Tasks Completed Today</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                    @forelse ($employees as $employee)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                            <!-- Name & Email -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('dashboard.employees.show', $employee->id) }}" class="flex items-center gap-3 hover:opacity-85 transition-opacity">
                                    <div class="w-9 h-9 rounded-full bg-sky-50 dark:bg-blue-950/20 text-skyAccent dark:text-blue-400 flex items-center justify-center font-bold text-sm">
                                        {{ substr($employee->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <span class="block font-bold text-slate-900 dark:text-white hover:text-skyAccent dark:hover:text-blue-400 transition-colors">{{ $employee->name }}</span>
                                        <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $employee->email }}</span>
                                    </div>
                                </a>
                            </td>

                            <!-- Dept / Desig -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="block font-semibold text-slate-800 dark:text-slate-200 text-xs">{{ $employee->department?->name ?? '-' }}</span>
                                <span class="block text-[11px] text-slate-400 mt-0.5">{{ $employee->designation?->name ?? '-' }}</span>
                            </td>

                            <!-- Attendance Status -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($employee->attendance_status === 'present')
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-50 border border-green-200 text-green-700 dark:bg-green-950/20 dark:border-green-800 dark:text-green-400">
                                        Present
                                    </span>
                                @elseif ($employee->attendance_status === 'late')
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 border border-amber-200 text-amber-700 dark:bg-amber-950/20 dark:border-amber-800 dark:text-amber-400">
                                        Late Arrival
                                    </span>
                                @else
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 border border-slate-200 text-slate-500 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400">
                                        Absent
                                    </span>
                                @endif
                            </td>

                            <!-- Active Timer Status -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($employee->active_timer)
                                    <div class="flex items-center gap-2" 
                                         x-data="{ 
                                             elapsedSeconds: {{ $employee->active_timer->started_at ? now()->diffInSeconds($employee->active_timer->started_at) : 0 }},
                                             formatTime(sec) {
                                                 let h = Math.floor(sec / 3600);
                                                 let m = Math.floor((sec % 3600) / 60);
                                                 let s = sec % 60;
                                                 return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
                                             }
                                         }"
                                         x-init="setInterval(() => { elapsedSeconds++ }, 1000)">
                                        <!-- Blinking green indicator -->
                                        <span class="relative flex h-2.5 w-2.5">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
                                        </span>
                                        <div class="text-xs">
                                            <span class="font-bold text-slate-800 dark:text-slate-200 block truncate max-w-[150px]">
                                                {{ $employee->active_timer->task?->title ?? 'No Task Assigned' }}
                                            </span>
                                            <span class="text-slate-400 font-mono tracking-tight" x-text="formatTime(elapsedSeconds)"></span>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-xs font-medium text-slate-400 dark:text-slate-500 flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                        Idle / No Active Timer
                                    </span>
                                @endif
                            </td>

                            <!-- Hours Worked Today -->
                            <td class="px-6 py-4 whitespace-nowrap text-center font-semibold text-slate-800 dark:text-slate-200">
                                {{ $employee->hours_worked_today }} hrs
                            </td>

                            <!-- Tasks Completed Today -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="px-2.5 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs">
                                    {{ $employee->tasks_completed_today }} Tasks
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <a href="{{ route('dashboard.employees.show', $employee->id) }}" 
                                   class="inline-flex px-3 py-1.5 rounded-lg border border-slate-200 hover:border-skyAccent text-slate-600 hover:text-skyAccent dark:border-slate-800 dark:hover:border-blue-500 dark:text-slate-400 dark:hover:text-blue-400 font-bold text-xs transition-colors">
                                    View Performance
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">
                                No employees assigned to your team. Seed the database to load records.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($employees->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/40">
                {{ $employees->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
