@extends('layouts.app')

@section('content')
<div class="space-y-8 animate-fade-in">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Attendance & Leaves</h2>
        </div>
        
        <!-- Month & Year Selector -->
        <form action="{{ route('employee.attendance.index') }}" method="GET" class="flex items-center gap-2">
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

    <!-- Attendance Performance Scorecards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Attendance Score Card -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="block text-[10px] font-bold text-slate-450 dark:text-slate-400 uppercase tracking-wider">Attendance Score</span>
                <span class="text-2xl font-black text-slate-900 dark:text-white block">{{ $metrics['attendance_score'] }}%</span>
                <span class="text-[10px] font-medium text-slate-400">Punctuality index</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-skyAccent via-indigo-500 to-purple-650 flex items-center justify-center text-white font-extrabold text-sm shadow-md animate-pulse">
                {{ $metrics['attendance_score'] }}
            </div>
        </div>

        <!-- Attendance Percentage Card -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="block text-[10px] font-bold text-slate-450 dark:text-slate-400 uppercase tracking-wider">Attendance Rate</span>
                <span class="text-2xl font-black text-slate-900 dark:text-white block">{{ $metrics['attendance_percentage'] }}%</span>
                <span class="text-[10px] font-medium text-slate-400">Present/late weekdays</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/20 text-emerald-600 flex items-center justify-center font-bold text-lg">
                {{ $metrics['present_days'] }}d
            </div>
        </div>

        <!-- Leave Utilization Card -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="block text-[10px] font-bold text-slate-450 dark:text-slate-400 uppercase tracking-wider">Leave Utilization</span>
                <span class="text-2xl font-black text-slate-900 dark:text-white block">{{ $metrics['leave_utilization'] }}%</span>
                <span class="text-[10px] font-medium text-slate-400">Approved: {{ $metrics['total_leave_days_year'] }} / 20 days</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/20 text-indigo-650 flex items-center justify-center font-bold text-lg">
                {{ $metrics['total_leave_days_year'] }}d
            </div>
        </div>

        <!-- Early Exits Card -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="block text-[10px] font-bold text-slate-455 dark:text-slate-400 uppercase tracking-wider">Early Exits & Late</span>
                <span class="text-2xl font-black text-slate-900 dark:text-white block">{{ $metrics['early_exits'] }} / {{ $metrics['late_days'] }}</span>
                <span class="text-[10px] font-medium text-slate-400">Early out / Late check-in</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/20 text-amber-600 flex items-center justify-center font-bold text-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
    </div>

    <!-- Calendar View & Request Form Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left Side: Interactive Calendar -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3 mb-6">
                    Monthly Calendar - {{ Carbon\Carbon::create()->month($month)->format('F Y') }}
                </h3>
                
                @php
                    $firstDayOfWeek = $startDate->dayOfWeek; // 0 (Sunday) to 6 (Saturday)
                    $daysInMonth = $startDate->daysInMonth;
                    $precedingDays = ($firstDayOfWeek === 0) ? 6 : $firstDayOfWeek - 1; // Mon standard

                    // Approved leave weekdays set
                    $approvedLeaveDates = [];
                    foreach ($leaves->where('status', 'approved') as $leave) {
                        $lTemp = clone $leave->start_date;
                        while ($lTemp <= $leave->end_date) {
                            $approvedLeaveDates[] = $lTemp->toDateString();
                            $lTemp->addDay();
                        }
                    }
                    $approvedLeaveDates = array_unique($approvedLeaveDates);
                @endphp

                <!-- Days of Week labels -->
                <div class="grid grid-cols-7 gap-2 mb-2 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">
                    <div>Mon</div>
                    <div>Tue</div>
                    <div>Wed</div>
                    <div>Thu</div>
                    <div>Fri</div>
                    <div class="text-slate-350">Sat</div>
                    <div class="text-slate-350">Sun</div>
                </div>

                <!-- Days Grid -->
                <div class="grid grid-cols-7 gap-2 text-center">
                    <!-- Blank cells for preceding days -->
                    @for ($i = 0; $i < $precedingDays; $i++)
                        <div class="h-14 bg-slate-50/50 dark:bg-slate-900/30 rounded-xl border border-transparent"></div>
                    @endfor

                    <!-- Active Days loop -->
                    @for ($d = 1; $d <= $daysInMonth; $d++)
                        @php
                            $currentDate = Carbon\Carbon::create($year, $month, $d);
                            $dateStr = $currentDate->toDateString();
                            $log = $logs->get($dateStr);
                            $isWeekend = $currentDate->isWeekend();

                            // Determine status styles
                            $bgClass = '';
                            $borderClass = 'border-slate-100 dark:border-slate-800';
                            $textClass = 'text-slate-800 dark:text-slate-200';
                            $title = 'Absent';

                            if ($log) {
                                if ($log->is_early_exit) {
                                    $bgClass = 'bg-orange-50 dark:bg-orange-950/20 text-orange-700 dark:text-orange-400';
                                    $borderClass = 'border-orange-200 dark:border-orange-900/40';
                                    $title = 'Present (Early Exit)';
                                } elseif ($log->status === 'late') {
                                    $bgClass = 'bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-400';
                                    $borderClass = 'border-amber-200 dark:border-amber-900/40';
                                    $title = 'Late Clock-in';
                                } else {
                                    $bgClass = 'bg-green-50 dark:bg-green-950/20 text-green-700 dark:text-green-400';
                                    $borderClass = 'border-green-200 dark:border-green-900/40';
                                    $title = 'Present';
                                }
                            } else {
                                if (in_array($dateStr, $approvedLeaveDates)) {
                                    $bgClass = 'bg-indigo-50 dark:bg-indigo-950/20 text-indigo-650 dark:text-indigo-400';
                                    $borderClass = 'border-indigo-200 dark:border-indigo-900/40';
                                    $title = 'Approved Leave';
                                } elseif ($isWeekend) {
                                    $bgClass = 'bg-slate-50/50 dark:bg-slate-850/20';
                                    $borderClass = 'border-slate-100 dark:border-slate-850/50';
                                    $textClass = 'text-slate-400';
                                    $title = 'Weekend';
                                } elseif ($currentDate->isFuture()) {
                                    $borderClass = 'border-dashed border-slate-200 dark:border-slate-800';
                                    $textClass = 'text-slate-400';
                                    $title = 'Upcoming Day';
                                } else {
                                    $bgClass = 'bg-red-50 dark:bg-red-950/20 text-red-700 dark:text-red-400';
                                    $borderClass = 'border-red-200 dark:border-red-900/40';
                                    $title = 'Unexcused Absent';
                                }
                            }
                        @endphp
                        
                        <div class="h-14 flex flex-col items-center justify-between p-1.5 rounded-xl border {{ $bgClass }} {{ $borderClass }} hover:scale-105 transition-transform" 
                             title="{{ $title }}: {{ $currentDate->format('M d, Y') }}">
                            <span class="text-xs font-bold leading-none block self-start {{ $textClass }}">{{ $d }}</span>
                            
                            @if ($log)
                                <span class="text-[8px] font-black leading-none uppercase block px-1 py-0.5 rounded
                                    {{ $log->is_early_exit ? 'bg-orange-200 dark:bg-orange-900' : ($log->status === 'late' ? 'bg-amber-250 dark:bg-amber-900' : 'bg-green-200 dark:bg-green-900') }}">
                                    {{ $log->is_early_exit ? 'E-Exit' : ($log->status === 'late' ? 'Late' : 'OK') }}
                                </span>
                            @elseif (in_array($dateStr, $approvedLeaveDates))
                                <span class="text-[8px] font-black leading-none uppercase block px-1 py-0.5 bg-indigo-200 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200 rounded">
                                    Leave
                                </span>
                            @elseif (!$isWeekend && !$currentDate->isFuture())
                                <span class="text-[8px] font-black leading-none uppercase block px-1 py-0.5 bg-red-200 dark:bg-red-900 text-red-800 dark:text-red-200 rounded">
                                    Absent
                                </span>
                            @endif
                        </div>
                    @endfor
                </div>

                <!-- Calendar Legend -->
                <div class="mt-6 border-t border-slate-100 dark:border-slate-800/80 pt-4 flex flex-wrap gap-4 items-center text-[10px] font-bold uppercase text-slate-500 tracking-wider">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-green-500"></span> Present</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-amber-500"></span> Late</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-orange-500"></span> Early Exit</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-red-500"></span> Absent</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-indigo-500"></span> Approved Leave</span>
                </div>
            </div>
        </div>

        <!-- Right Side: Submit Leave Request -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800/80 pb-3 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-skyAccent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    Request Leave
                </h3>

                <form action="{{ route('employee.leaves.store') }}" method="POST" class="space-y-4">
                    @csrf
                    
                    <div class="space-y-1">
                        <label for="leave_type" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Leave Type</label>
                        <select name="type" id="leave_type" required 
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-xs text-slate-800 dark:text-slate-100">
                            <option value="sick">Sick Leave</option>
                            <option value="casual">Casual Leave</option>
                            <option value="vacation">Vacation / Planned Leave</option>
                            <option value="other">Other Excusal</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label for="start_date" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Start Date</label>
                        <input type="date" name="start_date" id="start_date" required min="{{ Carbon\Carbon::today()->toDateString() }}"
                               class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-xs text-slate-800 dark:text-slate-100">
                    </div>

                    <div class="space-y-1">
                        <label for="end_date" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">End Date</label>
                        <input type="date" name="end_date" id="end_date" required min="{{ Carbon\Carbon::today()->toDateString() }}"
                               class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-xs text-slate-800 dark:text-slate-100">
                    </div>

                    <div class="space-y-1">
                        <label for="reason" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Reason for Excusal</label>
                        <textarea name="reason" id="reason" rows="3" placeholder="Provide context regarding your request..."
                                  class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-xs text-slate-805 dark:text-slate-205"></textarea>
                    </div>

                    <button type="submit" 
                            class="w-full py-2.5 bg-skyAccent hover:bg-sky-650 text-white font-bold text-xs rounded-xl shadow-sm hover:shadow transition-all text-center">
                        Submit Leave Request
                    </button>
                </form>
            </div>
        </div>

    </div>

    <!-- Leaves Requests History List -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
        <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800/80 pb-3 mb-4">
            Leave Requests & Status Log
        </h3>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/40 text-slate-500 dark:text-slate-400 font-bold uppercase border-b border-slate-200 dark:border-slate-800">
                        <th class="px-6 py-3">Dates Span</th>
                        <th class="px-6 py-3">Days</th>
                        <th class="px-6 py-3">Type</th>
                        <th class="px-6 py-3">Reason</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Reviewed By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-150 dark:divide-slate-800/50">
                    @forelse ($leaves as $leave)
                        @php
                            // Calculate days spanned
                            $days = $leave->start_date->diffInDays($leave->end_date) + 1;
                        @endphp
                        <tr class="hover:bg-slate-50/20 dark:hover:bg-slate-800/10">
                            <td class="px-6 py-4 font-semibold text-slate-800 dark:text-slate-200">
                                {{ $leave->start_date->format('M d, Y') }} &rarr; {{ $leave->end_date->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 text-slate-650">{{ $days }} workday(s)</td>
                            <td class="px-6 py-4">
                                <span class="inline-block px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-[10px] font-bold uppercase tracking-wider text-slate-550 dark:text-slate-400">
                                    {{ $leave->type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-500 max-w-xs truncate" title="{{ $leave->reason }}">{{ $leave->reason ?: 'No reason specified.' }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider
                                    @if ($leave->status === 'approved')
                                        bg-green-50 text-green-700 dark:bg-green-950/20 dark:text-green-400
                                    @elseif ($leave->status === 'rejected')
                                        bg-red-50 text-red-700 dark:bg-red-950/20 dark:text-red-400
                                    @else
                                        bg-amber-50 text-amber-705 dark:bg-amber-950/20 dark:text-amber-400
                                    @endif">
                                    {{ $leave->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-500 font-medium">
                                {{ $leave->approver ? $leave->approver->name : 'Pending Review' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400 italic">
                                No leaves requested yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
