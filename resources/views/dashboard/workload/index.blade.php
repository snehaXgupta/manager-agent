@extends('layouts.app')

@section('content')
<div class="space-y-8 animate-fade-in">
    <!-- Header -->
    <div>
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Workload Analysis</h2>
        <!-- <p class="text-sm text-slate-500 dark:text-slate-400">Analyze task distribution across team members and discover optimized resource reallocations.</p> -->
    </div>

    <!-- Recommendations Alert Box -->
    <div class="bg-gradient-to-r from-sky-50 to-indigo-50 dark:from-slate-900 dark:to-slate-850 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm space-y-3">
        <h3 class="font-bold text-slate-900 dark:text-white flex items-center gap-2 text-sm uppercase tracking-wide">
            <!-- Sparkles/AI suggestions icon -->
            <svg class="w-5 h-5 text-skyAccent dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            Workload Balancing Recommendations
        </h3>
        <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-350">
            @foreach ($workload['recommendations'] as $recommendation)
                <li class="flex items-start gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-skyAccent dark:bg-blue-400 mt-2 shrink-0"></span>
                    <span>{{ $recommendation }}</span>
                </li>
            @endforeach
        </ul>
    </div>

    <!-- Workload Stats Table -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/40 text-slate-500 dark:text-slate-400 text-xs font-bold uppercase border-b border-slate-200 dark:border-slate-800">
                        <th class="px-6 py-4">Employee</th>
                        <th class="px-6 py-4 text-center">Active Workload</th>
                        <th class="px-6 py-4 text-center">Completed Workload</th>
                        <th class="px-6 py-4 text-center">Avg Task Duration</th>
                        <th class="px-6 py-4 text-center">Allocation Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                    @forelse ($workload['team_workload'] as $member)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                            <!-- Name & Email -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center font-bold text-sm">
                                        {{ substr($member['name'], 0, 2) }}
                                    </div>
                                    <div>
                                        <span class="block font-bold text-slate-900 dark:text-white">{{ $member['name'] }}</span>
                                        <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $member['email'] }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Active Workload count -->
                            <td class="px-6 py-4 whitespace-nowrap text-center font-semibold text-slate-800 dark:text-slate-200">
                                {{ $member['active_tasks'] }} Tasks
                            </td>

                            <!-- Completed Workload count -->
                            <td class="px-6 py-4 whitespace-nowrap text-center text-slate-650 dark:text-slate-405">
                                {{ $member['completed_tasks'] }} Tasks
                            </td>

                            <!-- Average task duration -->
                            <td class="px-6 py-4 whitespace-nowrap text-center text-slate-500 dark:text-slate-400">
                                {{ $member['avg_task_duration'] }} hrs / task
                            </td>

                            <!-- Status Badge -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold border {{ $member['badge_class'] }}">
                                    {{ $member['status'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">
                                No workload records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination Links -->
    @if(isset($workload['paginator']) && $workload['paginator'])
        <div class="mt-6 flex justify-center">
            {{ $workload['paginator']->links() }}
        </div>
    @endif
</div>
@endsection
