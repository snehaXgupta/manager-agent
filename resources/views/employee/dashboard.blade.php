@extends('layouts.app')

@section('content')
<div class="space-y-8 animate-fade-in" x-data="{ 
    timerRunning: {{ $activeTimer ? 'true' : 'false' }},
    timerSeconds: {{ $activeTimer ? (int) abs(now()->diffInSeconds(\Illuminate\Support\Carbon::parse($activeTimer->started_at))) : 0 }},
    timerInterval: null,
    activeTaskId: {{ $activeTimer ? $activeTimer->task_id : 'null' }},
    startLiveTimer() {
        if (this.timerInterval) clearInterval(this.timerInterval);
        this.timerInterval = setInterval(() => {
            this.timerSeconds++;
        }, 1000);
    },
    formatTime(secs) {
        const h = Math.floor(secs / 3600).toString().padStart(2, '0');
        const m = Math.floor((secs % 3600) / 60).toString().padStart(2, '0');
        const s = (secs % 60).toString().padStart(2, '0');
        return `${h}:${m}:${s}`;
    },
    init() {
        if (this.timerRunning) {
            this.startLiveTimer();
        }
    }
}">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Employee Workspace</h2>
            <!-- <p class="text-sm text-slate-500 dark:text-slate-400">Welcome back, {{ $employee->name }}. Manage your tasks and track your work hours.</p> -->
        </div>
        
        <!-- Live Timer Badge -->
        <div x-show="timerRunning" 
             x-cloak
             class="px-4 py-2 bg-skyAccent/10 dark:bg-blue-950/40 border border-skyAccent/30 dark:border-blue-800 rounded-xl shadow-sm flex items-center gap-3">
            <span class="w-2.5 h-2.5 rounded-full bg-skyAccent animate-ping"></span>
            <span class="text-xs font-bold text-skyAccent dark:text-blue-400">Timer Active:</span>
            <span class="text-sm font-extrabold font-mono text-slate-800 dark:text-slate-100" x-text="formatTime(timerSeconds)">00:00:00</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Column: Tasks -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3 mb-6">Assigned Tasks</h3>
                
                <div class="space-y-4">
                    @forelse ($tasks as $task)
                        <div class="p-5 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-slate-100 dark:border-slate-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="space-y-1">
                                <div class="flex items-center gap-3">
                                    <h4 class="font-bold text-sm text-slate-800 dark:text-slate-100">{{ $task->title }}</h4>
                                    
                                    @if ($task->status === 'completed')
                                        <span class="inline-flex px-2 py-0.5 rounded bg-green-50 dark:bg-green-950/20 text-green-700 dark:text-green-400 text-[10px] font-bold border border-green-200 dark:border-green-800">Completed</span>
                                    @elseif ($task->status === 'in_progress')
                                        <span class="inline-flex px-2 py-0.5 rounded bg-sky-50 dark:bg-sky-950/20 text-skyAccent dark:text-sky-400 text-[10px] font-bold border border-skyAccent/30 animate-pulse">In Progress</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-400 text-[10px] font-bold border border-amber-200 dark:border-amber-800">Pending</span>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md">{{ $task->description }}</p>
                                
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 pt-2 text-[10px] text-slate-400 dark:text-slate-500 font-medium">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        Deadline: {{ $task->deadline ? \Illuminate\Support\Carbon::parse($task->deadline)->format('M d, Y') : 'No Deadline' }}
                                    </span>
                                    <span>&bull;</span>
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                        Project: <strong class="text-slate-700 dark:text-slate-300">{{ $task->project ? $task->project->name : 'N/A' }}</strong>
                                    </span>
                                    <span>&bull;</span>
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        Logged: <strong class="text-slate-700 dark:text-slate-300">{{ $task->total_logged_hours }} hrs</strong>
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                @if ($task->status === 'completed')
                                    <span class="text-slate-400 dark:text-slate-550 text-xs font-semibold flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        Closed
                                    </span>
                                @else
                                    @if ($activeTimer && $activeTimer->task_id === $task->id)
                                        <button @click="stopTimer({{ $employee->id }})" 
                                                class="px-4 py-2 bg-red-500 hover:bg-red-650 text-white font-bold rounded-xl shadow-sm hover:shadow text-xs transition-all active:scale-[0.98]">
                                            Stop Timer
                                        </button>
                                    @else
                                        <button @click="startTimer({{ $task->id }}, {{ $employee->id }})" 
                                                :disabled="timerRunning"
                                                :class="timerRunning ? 'opacity-40 cursor-not-allowed bg-slate-200 dark:bg-slate-800 text-slate-400' : 'bg-skyAccent hover:bg-sky-500 dark:bg-blue-600 dark:hover:bg-blue-500 text-white hover:shadow active:scale-[0.98]'"
                                                class="px-4 py-2 font-bold rounded-xl shadow-sm text-xs transition-all">
                                            Start Timer
                                        </button>
                                    @endif

                                    <form action="{{ route('employee.tasks.complete', $task->id) }}" method="POST" class="inline-block">
                                        @csrf
                                        <button type="submit" 
                                                class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white font-bold rounded-xl shadow-sm text-xs transition-all active:scale-[0.98]">
                                            Complete
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-sm text-slate-500 dark:text-slate-400">
                            No tasks assigned to you yet. Good job!
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Time Entry Logs -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3 mb-4">Recent Time Logs</h3>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-800 text-slate-500 text-xs font-bold uppercase">
                                <th class="py-3 px-2">Task</th>
                                <th class="py-3 px-2">Started At</th>
                                <th class="py-3 px-2">Duration</th>
                                <th class="py-3 px-2">Stopped At</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                            @forelse ($timeEntries as $entry)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                    <td class="py-3 px-2 font-bold text-slate-800 dark:text-slate-250">{{ $entry->task->title }}</td>
                                    <td class="py-3 px-2 text-slate-500 dark:text-slate-400">{{ \Illuminate\Support\Carbon::parse($entry->started_at)->format('M d, H:i') }}</td>
                                    <td class="py-3 px-2 font-semibold text-slate-700 dark:text-slate-300">
                                        @if ($entry->stopped_at)
                                            {{ round($entry->duration_seconds / 3600, 2) }} hrs
                                        @else
                                            <span class="text-skyAccent dark:text-blue-400 font-bold animate-pulse">Running</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-2 text-slate-400">
                                        @if ($entry->stopped_at)
                                            {{ \Illuminate\Support\Carbon::parse($entry->stopped_at)->format('M d, H:i') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-6 text-slate-500 dark:text-slate-400">No time entries recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sidebar Column: Clock In/Out & Info -->
        <div class="space-y-6">
            <!-- Clock In/Out Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm text-center">
                <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3 mb-4">Daily Attendance Clock</h3>
                
                @if (!$attendance)
                    <!-- Not Checked In -->
                    <div class="py-6 space-y-4">
                        <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto text-slate-400 dark:text-slate-550">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <span class="block text-sm font-semibold text-slate-700 dark:text-slate-350">Status: Clocked Out</span>
                            <span class="block text-xs text-slate-400 dark:text-slate-500">Record your starting hour for today.</span>
                        </div>
                        <form action="{{ route('employee.clock-in') }}" method="POST">
                            @csrf
                            <button type="submit" 
                                    class="w-full py-3 bg-emerald-500 hover:bg-emerald-600 text-white font-bold rounded-xl shadow-md hover:shadow active:scale-[0.98] transition-all text-xs">
                                Clock In Now
                            </button>
                        </form>
                    </div>
                @elseif (!$attendance->check_out)
                    <!-- Checked In, Not Checked Out -->
                    <div class="py-6 space-y-4">
                        <div class="w-16 h-16 rounded-full bg-emerald-500/10 flex items-center justify-center mx-auto text-emerald-500">
                            <svg class="w-8 h-8 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <span class="block text-sm font-bold text-emerald-600 dark:text-emerald-400">Status: On Duty</span>
                            <span class="block text-xs text-slate-500 dark:text-slate-400 mt-1">Clocked In at: <span class="font-mono font-bold">{{ \Illuminate\Support\Carbon::parse($attendance->check_in)->format('H:i') }}</span></span>
                        </div>
                        <form action="{{ route('employee.clock-out') }}" method="POST">
                            @csrf
                            <button type="submit" 
                                    class="w-full py-3 bg-red-500 hover:bg-red-600 text-white font-bold rounded-xl shadow-md hover:shadow active:scale-[0.98] transition-all text-xs">
                                Clock Out & Stop Timers
                            </button>
                        </form>
                    </div>
                @else
                    <!-- Completed Day -->
                    <div class="py-6 space-y-4">
                        <div class="w-16 h-16 rounded-full bg-blue-50 dark:bg-blue-950/20 flex items-center justify-center mx-auto text-skyAccent">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <span class="block text-sm font-bold text-slate-700 dark:text-slate-350">Status: Completed</span>
                            <span class="block text-xs text-slate-500 dark:text-slate-400 mt-1">Clock In: <span class="font-mono font-semibold">{{ \Illuminate\Support\Carbon::parse($attendance->check_in)->format('H:i') }}</span></span>
                            <span class="block text-xs text-slate-500 dark:text-slate-400">Clock Out: <span class="font-mono font-semibold">{{ \Illuminate\Support\Carbon::parse($attendance->check_out)->format('H:i') }}</span></span>
                        </div>
                        <div class="w-full py-2 bg-slate-50 dark:bg-slate-800/40 rounded-xl text-[11px] text-slate-400 dark:text-slate-500 font-semibold border border-slate-100 dark:border-slate-800/60">
                            Shift Closed
                        </div>
                    </div>
                @endif
            </div>

            <!-- Active Risk Alerts Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3 mb-4">Workspace Alerts</h3>
                
                @if($activeAlerts->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($activeAlerts as $alert)
                            @php
                                $badgeColor = $alert->risk_level === 'high' 
                                    ? 'bg-rose-500/10 text-rose-500 border-rose-500/20' 
                                    : 'bg-amber-500/10 text-amber-500 border-amber-500/20';
                            @endphp
                            <div class="p-3.5 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-150 dark:border-slate-800 flex items-start gap-2.5">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0 mt-1 {{ $alert->risk_level === 'high' ? 'bg-rose-500' : 'bg-amber-500' }} animate-pulse"></span>
                                <div class="space-y-1">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200">{{ ucfirst(str_replace('_', ' ', $alert->risk_type)) }}</span>
                                        <span class="px-1.5 py-0.5 rounded text-[8px] font-bold border {{ $badgeColor }} uppercase tracking-wider">{{ $alert->risk_level }}</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium leading-relaxed">{{ $alert->reason }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-6 text-center text-xs text-slate-400 dark:text-slate-500 italic space-y-1">
                        <svg class="w-8 h-8 text-slate-350 dark:text-slate-750 mx-auto mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                        All checks clear. Your workspace health is excellent!
                    </div>
                @endif
            </div>

            <!-- Profile Info Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3 mb-4">My Account Details</h3>
                <div class="space-y-3 text-xs">
                    <div>
                        <span class="block text-slate-400 dark:text-slate-500 uppercase tracking-wider font-bold text-[10px]">Supervisor</span>
                        <span class="text-sm font-bold text-slate-800 dark:text-slate-200">
                            {{ $employee->manager ? $employee->manager->name : 'Unassigned (No Supervisor)' }}
                        </span>
                        @if ($employee->manager)
                            <span class="block text-slate-400 dark:text-slate-500 text-[10px]">{{ $employee->manager->email }}</span>
                        @endif
                    </div>
                    
                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                        <span class="block text-slate-400 dark:text-slate-500 uppercase tracking-wider font-bold text-[10px]">Active Role Context</span>
                        <span class="text-sm font-bold text-slate-850 dark:text-slate-250">
                            {{ ucfirst(session('active_role', $employee->role)) }}
                        </span>
                        @if ($employee->role === 'manager')
                            <span class="block text-slate-400 dark:text-slate-500 text-[10px]">Database original role: Manager</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alpine + Fetch API timer integrations -->
<script>
function startTimer(taskId, userId) {
    fetch('/web/timer/start', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ task_id: taskId, user_id: userId })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => { throw new Error(err.error || 'Failed to start timer'); });
        }
        return response.json();
    })
    .then(data => {
        window.location.reload();
    })
    .catch(error => {
        alert(error.message);
    });
}

function stopTimer(userId) {
    fetch('/web/timer/stop', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ user_id: userId })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => { throw new Error(err.error || 'Failed to stop timer'); });
        }
        return response.json();
    })
    .then(data => {
        window.location.reload();
    })
    .catch(error => {
        alert(error.message);
    });
}
</script>
@endsection
