@extends('layouts.app')

@section('content')
<div class="space-y-8 animate-fade-in" x-data="{ showGenerateModal: false }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Performance Reports Archive</h2>
            <!-- <p class="text-sm text-slate-500 dark:text-slate-400">View operational check archives, analyze metrics histories, and generate new reports on demand.</p> -->
        </div>
        <button @click="showGenerateModal = true" 
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm bg-skyAccent hover:bg-sky-650 text-white shadow-sm hover:shadow transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Generate New Report
        </button>
    </div>

    <!-- History Filters Form -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm">
        <form action="{{ route('dashboard.reports.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
            <!-- Filter by Type -->
            <div class="flex-1 min-w-[150px] space-y-1">
                <label for="filter_type" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Report Type</label>
                <select name="type" id="filter_type"
                        class="w-full px-3 py-2 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs rounded-xl outline-none text-slate-800 dark:text-slate-100">
                    <option value="">All Reports</option>
                    <option value="daily" {{ request('type') === 'daily' ? 'selected' : '' }}>Daily Reports</option>
                    <option value="weekly" {{ request('type') === 'weekly' ? 'selected' : '' }}>Weekly Reports</option>
                    <option value="monthly" {{ request('type') === 'monthly' ? 'selected' : '' }}>Monthly Reports</option>
                    <option value="project_completion" {{ request('type') === 'project_completion' ? 'selected' : '' }}>Project Completion Reports</option>
                    <option value="delayed_projects" {{ request('type') === 'delayed_projects' ? 'selected' : '' }}>Delayed Projects Reports</option>
                    <option value="team_wise_projects" {{ request('type') === 'team_wise_projects' ? 'selected' : '' }}>Team-wise Projects Reports</option>
                </select>
            </div>

            <!-- Start Date -->
            <div class="w-full sm:w-[160px] space-y-1">
                <label for="filter_start_date" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Generated From</label>
                <input type="date" name="start_date" id="filter_start_date" value="{{ request('start_date') }}"
                       class="w-full px-3 py-2 border border-slate-200 dark:border-slate-800 bg-transparent text-xs rounded-xl outline-none text-slate-800 dark:text-slate-100">
            </div>

            <!-- End Date -->
            <div class="w-full sm:w-[160px] space-y-1">
                <label for="filter_end_date" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Generated To</label>
                <input type="date" name="end_date" id="filter_end_date" value="{{ request('end_date') }}"
                       class="w-full px-3 py-2 border border-slate-200 dark:border-slate-800 bg-transparent text-xs rounded-xl outline-none text-slate-800 dark:text-slate-100">
            </div>

            <!-- Buttons -->
            <div class="flex items-center gap-2 shrink-0">
                <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 dark:bg-slate-850 dark:hover:bg-slate-750 text-white font-bold text-xs rounded-xl shadow-sm hover:shadow transition-all text-center whitespace-nowrap">
                    Apply Filters
                </button>
                @if(request()->anyFilled(['type', 'start_date', 'end_date']))
                    <a href="{{ route('dashboard.reports.index') }}" class="px-4 py-2 border border-slate-250 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-850 text-slate-700 dark:text-slate-350 font-bold text-xs rounded-xl transition-all text-center whitespace-nowrap">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Reports Table -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/40 text-slate-500 dark:text-slate-400 text-xs font-bold uppercase border-b border-slate-200 dark:border-slate-800">
                        <th class="px-6 py-4">Report ID</th>
                        <th class="px-6 py-4">Calculation Date</th>
                        <th class="px-6 py-4">Report Type</th>
                        <th class="px-6 py-4 text-center">Manager Score</th>
                        <th class="px-6 py-4">Key Metrics Summary</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                    @forelse ($reports as $report)
                        <tr class="hover:bg-slate-50/30 dark:hover:bg-slate-800/10 transition-colors cursor-pointer" 
                            onclick="window.location.href='{{ route('dashboard.reports.show', $report->id) }}'">
                            <!-- ID -->
                            <td class="px-6 py-4 whitespace-nowrap font-mono text-xs text-slate-500 dark:text-slate-400">
                                #REP-{{ str_pad($report->id, 5, '0', STR_PAD_LEFT) }}
                            </td>

                            <!-- Date -->
                            <td class="px-6 py-4 whitespace-nowrap text-slate-700 dark:text-slate-300 font-medium">
                                {{ $report->generated_at->format('M d, Y') }}
                            </td>

                            <!-- Type -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold uppercase border
                                    @if($report->report_type === 'daily')
                                        bg-green-50 text-green-700 border-green-200 dark:bg-green-950/20 dark:text-green-400 dark:border-green-800
                                    @elseif($report->report_type === 'weekly')
                                        bg-sky-50 text-skyAccent border-sky-200 dark:bg-blue-950/20 dark:text-blue-400 dark:border-blue-800
                                    @elseif($report->report_type === 'project_completion')
                                        bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/20 dark:text-indigo-400 dark:border-indigo-800
                                    @elseif($report->report_type === 'delayed_projects')
                                        bg-red-50 text-red-700 border-red-200 dark:bg-red-950/20 dark:text-red-400 dark:border-red-800
                                    @elseif($report->report_type === 'team_wise_projects')
                                        bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-950/20 dark:text-orange-400 dark:border-orange-800
                                    @else
                                        bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/20 dark:text-purple-400 dark:border-purple-800
                                    @endif">
                                    {{ str_replace('_', ' ', $report->report_type) }}
                                </span>
                            </td>

                            <!-- Manager Score -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="text-sm font-bold text-skyAccent dark:text-blue-400">
                                    {{ $report->manager_score }}%
                                </span>
                            </td>

                            <!-- Metrics snapshot details -->
                            <td class="px-6 py-4 text-xs text-slate-505">
                                @if($report->report_type === 'project_completion')
                                    <div class="max-w-sm font-medium">
                                        Project: <span class="font-bold text-slate-900 dark:text-white">{{ $report->metrics_json['project_name'] ?? 'N/A' }}</span><br>
                                        Completion: <span class="font-bold text-slate-800 dark:text-slate-200">{{ $report->metrics_json['task_completion_rate'] ?? 0 }}%</span> | Health: <span class="font-bold text-slate-800 dark:text-slate-200">{{ $report->metrics_json['health_score'] ?? 0 }}%</span>
                                    </div>
                                @elseif($report->report_type === 'delayed_projects')
                                    <div class="max-w-sm font-medium">
                                        Delayed Projects: <span class="font-bold text-red-600">{{ $report->metrics_json['total_delayed_count'] ?? 0 }}</span><br>
                                        Average Health: <span class="font-bold text-slate-850 dark:text-slate-200">{{ $report->metrics_json['average_health'] ?? 0 }}%</span>
                                    </div>
                                @elseif($report->report_type === 'team_wise_projects')
                                    <div class="max-w-sm font-medium">
                                        Teams Tracked: <span class="font-bold text-slate-900 dark:text-white">{{ $report->metrics_json['total_teams_count'] ?? 0 }}</span><br>
                                        Average Completion: <span class="font-bold text-slate-800 dark:text-slate-200">{{ $report->metrics_json['average_completion_rate'] ?? 0 }}%</span>
                                    </div>
                                @else
                                    <div class="grid grid-cols-2 gap-x-4 gap-y-1 max-w-sm">
                                        <div>Completion: <span class="font-bold text-slate-800 dark:text-slate-200">{{ $report->metrics_json['task_completion_rate'] ?? '0' }}%</span></div>
                                        <div>Productivity: <span class="font-bold text-slate-800 dark:text-slate-200">{{ $report->metrics_json['productivity_score'] ?? '0' }}%</span></div>
                                        <div>Adherence: <span class="font-bold text-slate-800 dark:text-slate-200">{{ $report->metrics_json['deadline_adherence_rate'] ?? '0' }}%</span></div>
                                        <div>Consistency: <span class="font-bold text-slate-800 dark:text-slate-200">{{ $report->metrics_json['consistency_score'] ?? '0' }}%</span></div>
                                    </div>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <a href="{{ route('dashboard.reports.show', $report->id) }}" class="inline-flex px-3 py-1.5 rounded-lg border border-slate-200 hover:border-skyAccent text-slate-650 hover:text-skyAccent dark:border-slate-850 dark:hover:border-blue-800 dark:text-slate-400 dark:hover:text-blue-400 font-bold text-xs transition-colors">
                                    View Full Report &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">
                                No performance checks match your filters. Try clearing your filters or generate a new report.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ON-DEMAND REPORT GENERATION MODAL -->
    <div x-cloak x-show="showGenerateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div @click="showGenerateModal = false" class="absolute inset-0 bg-slate-950/40"></div>
        <!-- Modal Card -->
        <div class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 w-full max-w-md shadow-2xl animate-scale-in">
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">Generate Operations Report</h3>
            <p class="text-xs text-slate-500 mb-4">Select the aggregation type and date limits. Leaving dates blank will auto-compute standard intervals.</p>
            
            <form action="{{ route('dashboard.reports.store') }}" method="POST" class="space-y-4" x-data="{ reportType: 'weekly' }">
                @csrf
                <!-- Report Type -->
                <div class="space-y-1">
                    <label for="report_type" class="block text-xs font-bold text-slate-500 uppercase">Report Scope</label>
                    <select name="report_type" id="report_type" required x-model="reportType"
                            class="w-full px-4 py-2 border border-slate-250 dark:border-slate-800 bg-white dark:bg-slate-900 text-sm rounded-xl outline-none text-slate-800 dark:text-slate-100">
                        <option value="daily">Daily Report (Today)</option>
                        <option value="weekly">Weekly Report (Last 7 Days)</option>
                        <option value="monthly">Monthly Report (Last 30 Days)</option>
                        <option value="project_completion">Project Completion Report (Single Workspace)</option>
                        <option value="delayed_projects">Delayed Projects Report</option>
                        <option value="team_wise_projects">Team-wise Projects Report</option>
                    </select>
                </div>

                <!-- Conditional Project Dropdown -->
                <div class="space-y-1" x-show="reportType === 'project_completion'" x-cloak x-collapse>
                    <label for="project_id" class="block text-xs font-bold text-slate-500 uppercase">Select Target Project</label>
                    <select name="project_id" id="project_id" :required="reportType === 'project_completion'"
                            class="w-full px-4 py-2 border border-slate-250 dark:border-slate-800 bg-white dark:bg-slate-900 text-sm rounded-xl outline-none text-slate-800 dark:text-slate-100">
                        <option value="">-- Select Project --</option>
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Start Date -->
                <div class="space-y-1">
                    <label for="start_date" class="block text-xs font-bold text-slate-500 uppercase">Custom Start Date (Optional)</label>
                    <input type="date" name="start_date" id="start_date"
                           class="w-full px-4 py-2 border border-slate-250 dark:border-slate-800 bg-transparent text-sm rounded-xl outline-none text-slate-800 dark:text-slate-105">
                </div>

                <!-- End Date -->
                <div class="space-y-1">
                    <label for="end_date" class="block text-xs font-bold text-slate-500 uppercase">Custom End Date (Optional)</label>
                    <input type="date" name="end_date" id="end_date"
                           class="w-full px-4 py-2 border border-slate-250 dark:border-slate-800 bg-transparent text-sm rounded-xl outline-none text-slate-800 dark:text-slate-105">
                </div>
                
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showGenerateModal = false"
                            class="px-4 py-2 rounded-xl text-xs font-semibold border border-slate-200 dark:border-slate-800 text-slate-750 dark:text-slate-350">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-xl text-xs font-bold bg-skyAccent hover:bg-sky-650 text-white shadow-sm">
                        Generate & Save Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
