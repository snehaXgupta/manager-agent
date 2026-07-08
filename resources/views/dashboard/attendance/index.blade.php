@extends('layouts.app')

@section('content')
<div class="space-y-8 animate-fade-in">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Team Attendance & Leaves</h2>
        </div>
        
        <!-- Period Filter -->
        <form action="{{ route('dashboard.attendance.index') }}" method="GET" class="flex items-center gap-2">
            <select name="month" onchange="this.form.submit()" 
                    class="px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs font-semibold rounded-xl outline-none text-slate-800 dark:text-slate-100">
                @for ($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $month === $m ? 'selected' : '' }}>
                        {{ Carbon\Carbon::create()->month($m)->format('F') }}
                    </option>
                @endfor
            </select>
            <select name="year" onchange="this.form.submit()" 
                    class="px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs font-semibold rounded-xl outline-none text-slate-800 dark:text-slate-100">
                @for ($y = Carbon\Carbon::now()->year - 2; $y <= Carbon\Carbon::now()->year + 1; $y++)
                    <option value="{{ $y }}" {{ $year === $y ? 'selected' : '' }}>
                        {{ $y }}
                    </option>
                @endfor
            </select>
        </form>
    </div>

    <!-- Team Metrics Scorecards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Team Avg Attendance Score -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="block text-[10px] font-bold text-slate-450 dark:text-slate-400 uppercase tracking-wider">Avg Team Score</span>
                <span class="text-2xl font-black text-slate-900 dark:text-white block">{{ $avgScore }}%</span>
                <span class="text-[10px] font-medium text-slate-400">Team punctuality rating</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-skyAccent via-indigo-500 to-purple-650 flex items-center justify-center text-white font-extrabold text-sm shadow-md animate-pulse">
                {{ round($avgScore) }}
            </div>
        </div>

        <!-- Team Avg Attendance Rate -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="block text-[10px] font-bold text-slate-450 dark:text-slate-400 uppercase tracking-wider">Avg Attendance Rate</span>
                <span class="text-2xl font-black text-slate-900 dark:text-white block">{{ $avgAttendance }}%</span>
                <span class="text-[10px] font-medium text-slate-400">Punctual & Late weekdays</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/20 text-emerald-600 flex items-center justify-center font-bold text-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>

        <!-- Team Avg Leaves Year -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="block text-[10px] font-bold text-slate-450 dark:text-slate-400 uppercase tracking-wider">Team Leave Utilization</span>
                <span class="text-2xl font-black text-slate-900 dark:text-white block">{{ round($avgLeaveUtilization, 1) }}%</span>
                <span class="text-[10px] font-medium text-slate-400">Avg {{ $avgLeaveDays }} days taken in year</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/20 text-indigo-650 flex items-center justify-center font-bold text-lg">
                {{ $avgLeaveDays }}d
            </div>
        </div>

        <!-- Pending Leave requests count -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="block text-[10px] font-bold text-slate-455 dark:text-slate-400 uppercase tracking-wider">Pending Leaves</span>
                <span class="text-2xl font-black text-slate-900 dark:text-white block">{{ $pendingLeaves->count() }} Requests</span>
                <span class="text-[10px] font-medium text-slate-400">Awaiting your review</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/20 text-amber-600 flex items-center justify-center font-bold text-lg">
                {{ $pendingLeaves->count() }}
            </div>
        </div>
    </div>

    <!-- Trend Chart & Pending Requests Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Absenteeism Trends Visual Chart -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between h-full">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3 mb-6">
                        Absenteeism Trends
                    </h3>
                    <p class="text-xs text-slate-400 mb-6">Total unexcused absences count across all direct reports during the last 4 weeks.</p>
                </div>

                <!-- Custom Premium CSS Bar Chart -->
                <div class="flex items-end justify-between h-48 px-4 pb-2 border-b border-slate-100 dark:border-slate-800">
                    @php
                        $maxAbs = max(1, collect($absenteeismTrends)->max('absences'));
                    @endphp
                    @foreach ($absenteeismTrends as $trend)
                        @php
                            $heightPercent = ($trend['absences'] / $maxAbs) * 100;
                        @endphp
                        <div class="flex flex-col items-center flex-1 group">
                            <!-- Popover count -->
                            <span class="text-[10px] font-bold text-slate-700 dark:text-slate-350 opacity-0 group-hover:opacity-100 transition-opacity mb-1 leading-none">
                                {{ $trend['absences'] }} abs
                            </span>
                            <!-- Bar container -->
                            <div class="w-8 bg-gradient-to-t from-red-500 to-rose-400 dark:from-red-650 dark:to-rose-500 rounded-t-lg transition-all duration-700 ease-out" 
                                 style="height: {{ max(4, $heightPercent) }}px; min-height: 4px;"
                                 title="{{ $trend['absences'] }} absences in week of {{ $trend['label'] }}">
                            </div>
                            <!-- Date label -->
                            <span class="text-[9px] font-bold text-slate-400 mt-2 block whitespace-nowrap">{{ $trend['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Pending Leave Requests review block -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm h-full">
                <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3 mb-4">
                    Leave Requests Awaiting Approval ({{ $pendingLeaves->count() }})
                </h3>

                <div class="overflow-y-auto max-h-60 pr-1 divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($pendingLeaves as $leave)
                        @php
                            $days = $leave->start_date->diffInDays($leave->end_date) + 1;
                        @endphp
                        <div class="py-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-slate-900 dark:text-white">{{ $leave->user->name }}</span>
                                    <span class="inline-block px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-[9px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        {{ $leave->type }}
                                    </span>
                                </div>
                                <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                                    {{ $leave->start_date->format('M d, Y') }} &rarr; {{ $leave->end_date->format('M d, Y') }} ({{ $days }} workday(s))
                                </div>
                                <div class="text-xs text-slate-450 italic">
                                    "{{ $leave->reason ?: 'No reason specified.' }}"
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <!-- Reject Form -->
                                <form action="{{ route('dashboard.leaves.reject', $leave->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" 
                                            class="px-3 py-1.5 rounded-lg border border-red-200 dark:border-red-900/40 text-red-500 hover:bg-red-50 dark:hover:bg-red-950/20 text-xs font-bold transition-all">
                                        Reject
                                    </button>
                                </form>
                                <!-- Approve Form -->
                                <form action="{{ route('dashboard.leaves.approve', $leave->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" 
                                            class="px-3 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold shadow-sm transition-all">
                                        Approve
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12 text-xs text-slate-400 italic">
                            All caught up! No pending leave requests to review.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>

    <!-- Direct Reports Attendance Grid -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
        <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3 mb-6">
            Direct Reports Attendance Log ({{ Carbon\Carbon::create()->month($month)->format('F Y') }})
        </h3>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/40 text-slate-500 dark:text-slate-400 font-bold uppercase border-b border-slate-200 dark:border-slate-800">
                        <th class="px-6 py-3">Employee</th>
                        <th class="px-6 py-3 text-center">Attendance Score</th>
                        <th class="px-6 py-3 text-center">Attendance %</th>
                        <th class="px-6 py-3 text-center">Yearly Leaves Taken</th>
                        <th class="px-6 py-3 text-center">Late Arrivals</th>
                        <th class="px-6 py-3 text-center">Early Exits</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-150 dark:divide-slate-800/50">
                    @forelse($employeesMetrics as $record)
                        @php
                            $emp = $record['employee'];
                            $m = $record['metrics'];
                            
                            // Determine rating color
                            $scoreColor = 'text-green-500 bg-green-50 dark:bg-green-950/20';
                            if ($m['attendance_score'] < 60) {
                                $scoreColor = 'text-red-500 bg-red-50 dark:bg-red-950/20';
                            } elseif ($m['attendance_score'] < 80) {
                                $scoreColor = 'text-amber-500 bg-amber-50 dark:bg-amber-950/20';
                            }
                        @endphp
                        <tr class="hover:bg-slate-50/20 dark:hover:bg-slate-800/10">
                            <!-- Name/Email -->
                            <td class="px-6 py-4">
                                <span class="block font-bold text-slate-900 dark:text-white">{{ $emp->name }}</span>
                                <span class="block text-[10px] text-slate-400">{{ $emp->email }}</span>
                            </td>
                            
                            <!-- Score -->
                            <td class="px-6 py-4 text-center">
                                <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-black {{ $scoreColor }}">
                                    {{ $m['attendance_score'] }}%
                                </span>
                            </td>
                            
                            <!-- Rate -->
                            <td class="px-6 py-4 text-center font-bold text-slate-800 dark:text-slate-200">
                                {{ $m['attendance_percentage'] }}%
                            </td>
                            
                            <!-- Leaves -->
                            <td class="px-6 py-4 text-center font-bold text-slate-700 dark:text-slate-350">
                                {{ $m['total_leave_days_year'] }} / 20 days
                            </td>

                            <!-- Late -->
                            <td class="px-6 py-4 text-center font-bold {{ $m['late_days'] > 0 ? 'text-amber-600' : 'text-slate-400' }}">
                                {{ $m['late_days'] }} times
                            </td>

                            <!-- Early Exit -->
                            <td class="px-6 py-4 text-center font-bold {{ $m['early_exits'] > 0 ? 'text-orange-600' : 'text-slate-400' }}">
                                {{ $m['early_exits'] }} times
                            </td>

                            <!-- Action -->
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('dashboard.employees.show', $emp->id) }}" 
                                   class="inline-flex px-2.5 py-1.5 rounded-lg border border-slate-200 hover:border-skyAccent dark:border-slate-850 dark:hover:border-blue-800 text-slate-600 hover:text-skyAccent dark:text-slate-400 dark:hover:text-blue-400 font-bold transition-colors">
                                    View Employee Profile
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-400 italic">
                                No direct report employees found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Links -->
        <div class="mt-4 px-6 py-4 border-t border-slate-150 dark:border-slate-800/60">
            {{ $employeesPaginator->links() }}
        </div>
    </div>
</div>
@endsection
