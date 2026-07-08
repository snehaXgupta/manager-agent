@extends('layouts.app')

@section('content')
<div class="space-y-8 animate-fade-in">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">GitLab Engineering Management</h2>
            <!-- <p class="text-sm text-slate-500 dark:text-slate-400"></p> -->
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex px-3 py-1 bg-green-50 dark:bg-green-950/20 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800 rounded-xl text-xs font-semibold gap-1.5 items-center">
                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                Webhook Sync Active
            </span>
        </div>
    </div>

    <!-- Widgets Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
        <!-- Projects -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-2xl shadow-sm flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-450 uppercase tracking-wider">Projects</span>
            <span class="text-2xl font-black text-slate-850 dark:text-white mt-1">{{ $widgets['total_projects'] }}</span>
        </div>
        <!-- Repos -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-2xl shadow-sm flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-450 uppercase tracking-wider">Repositories</span>
            <span class="text-2xl font-black text-slate-850 dark:text-white mt-1">{{ $widgets['total_repositories'] }}</span>
        </div>
        <!-- Commits -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-2xl shadow-sm flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-450 uppercase tracking-wider">Commits</span>
            <span class="text-2xl font-black text-skyAccent mt-1">{{ $widgets['total_commits'] }}</span>
        </div>
        <!-- Open MRs -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-2xl shadow-sm flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-450 uppercase tracking-wider">Open MRs</span>
            <span class="text-2xl font-black text-indigo-500 mt-1">{{ $widgets['open_mrs'] }}</span>
        </div>
        <!-- Merged MRs -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-2xl shadow-sm flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-450 uppercase tracking-wider">Merged MRs</span>
            <span class="text-2xl font-black text-emerald-500 mt-1">{{ $widgets['merged_mrs'] }}</span>
        </div>
        <!-- Reviews -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-2xl shadow-sm flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-450 uppercase tracking-wider">Pending Revs</span>
            <span class="text-2xl font-black text-amber-500 mt-1">{{ $widgets['pending_reviews'] }}</span>
        </div>
        <!-- Approvals -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-2xl shadow-sm flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-450 uppercase tracking-wider">Approvals</span>
            <span class="text-2xl font-black text-violet-500 mt-1">{{ $widgets['approvals'] }}</span>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Commits per Day (Line Chart) -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm lg:col-span-2 space-y-4">
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-250 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 pb-3">Commits Trend (Last 7 Days)</h3>
            <div class="h-64 relative">
                <canvas id="commitsTrendChart"></canvas>
            </div>
        </div>

        <!-- MR Status Distribution & Contributions -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm space-y-6">
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-250 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 pb-3">MR States & Top Contributors</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="h-32 relative">
                    <canvas id="mrDistributionChart"></canvas>
                </div>
                <div class="flex flex-col justify-center text-xs space-y-1.5 text-slate-500 dark:text-slate-400">
                    <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-blue-500 block"></span> Opened ({{ $mrDistribution['Opened'] }})</div>
                    <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-emerald-500 block"></span> Merged ({{ $mrDistribution['Merged'] }})</div>
                    <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-amber-500 block"></span> Approved ({{ $mrDistribution['Approved'] }})</div>
                    <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-red-500 block"></span> Rejected ({{ $mrDistribution['Rejected'] }})</div>
                </div>
            </div>
            
            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-3">
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Top Contributors</span>
                <div class="space-y-2">
                    @forelse($employeeContributions as $name => $count)
                        <div class="flex items-center justify-between text-xs font-semibold text-slate-700 dark:text-slate-300">
                            <span>{{ $name }}</span>
                            <span class="px-2 py-0.5 rounded bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 text-skyAccent">{{ $count }} commits</span>
                        </div>
                    @empty
                        <span class="text-xs text-slate-400 italic">No contributions synced yet.</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Manager MR Approval Workflow -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Pending Merge Request Approvals</h3>
            <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-indigo-50 dark:bg-blue-950/30 text-indigo-600 dark:text-blue-400">
                {{ $pendingMergeRequests->count() }} Pending
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-850/30 text-slate-500 dark:text-slate-400 text-xs font-bold uppercase border-b border-slate-200 dark:border-slate-800">
                        <th class="px-6 py-4">Title / Repository</th>
                        <th class="px-6 py-4">Author</th>
                        <th class="px-6 py-4">Branches</th>
                        <th class="px-6 py-4">Files Changed</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-150 dark:divide-slate-800/60 text-sm">
                    @forelse($pendingMergeRequests as $mr)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10 transition-colors">
                            <td class="px-6 py-4">
                                <span class="block font-semibold text-slate-900 dark:text-white">{{ $mr->title }}</span>
                                <span class="block text-xs text-slate-400">
                                    Repo: <strong>{{ $mr->repository->repository_name }}</strong>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center font-bold text-[10px] text-slate-600 dark:text-slate-350 shrink-0">
                                        {{ substr($mr->employee->name, 0, 2) }}
                                    </div>
                                    <span class="text-xs font-medium text-slate-800 dark:text-slate-200">
                                        {{ $mr->employee->name }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs font-mono text-slate-500">
                                {{ $mr->source_branch }} → {{ $mr->target_branch }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-600 dark:text-slate-400">
                                    {{ $mr->files_changed_count }} files
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-bold space-x-2">
                                <!-- View / Open in GitLab -->
                                <a href="{{ $mr->repository->repository_url }}/-/merge_requests/{{ $mr->gitlab_mr_id }}" target="_blank" 
                                   class="inline-block text-slate-500 hover:text-slate-700 dark:hover:text-white transition-colors">
                                    Open GitLab
                                </a>
                                <!-- Approve -->
                                <form action="{{ route('dashboard.mr.approve', $mr->id) }}" method="POST" class="inline-block">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300">
                                        Approve
                                    </button>
                                </form>
                                <!-- Reject -->
                                <form action="{{ route('dashboard.mr.reject', $mr->id) }}" method="POST" class="inline-block">
                                    @csrf
                                    <button type="submit" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                        Reject
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-450 italic">
                                No pending merge request approvals.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Live Activity Feed -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-6">
        <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3">Engineering Activity Feed</h3>
        
        <div class="space-y-6 max-h-96 overflow-y-auto pr-2">
            @forelse($activityFeed as $activity)
                <div class="flex items-start gap-4 p-3 bg-slate-50/50 dark:bg-slate-900/30 rounded-2xl border border-slate-150/40 hover:bg-slate-100 dark:hover:bg-slate-800/20 transition-colors">
                    <div class="p-2 rounded-xl shrink-0 mt-0.5 
                        @if($activity['type'] === 'commit') bg-blue-50 text-blue-500 dark:bg-blue-950/20 dark:text-blue-400
                        @else bg-purple-50 text-purple-500 dark:bg-purple-950/20 dark:text-purple-400 @endif">
                        @if($activity['type'] === 'commit')
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" /></svg>
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                        @endif
                    </div>
                    <div class="flex-1 text-xs">
                        <div class="flex justify-between items-start gap-2">
                            <div>
                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $activity['title'] }}</span>
                                <span class="block text-slate-500 mt-0.5">by {{ $activity['user'] }}</span>
                            </div>
                            <span class="text-[10px] text-slate-400 font-semibold shrink-0">{{ $activity['time']->diffForHumans() }}</span>
                        </div>
                        <div class="text-[10px] text-slate-400 mt-2 font-mono bg-slate-100 dark:bg-slate-800/80 p-2 rounded-lg inline-block">
                            {{ $activity['meta'] }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-6 text-xs text-slate-400 italic">
                    No engineering activity logged yet.
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Load Chart.js and script charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // 1. Commits Trend Chart
        const commitsTrendCtx = document.getElementById('commitsTrendChart');
        if (commitsTrendCtx) {
            new Chart(commitsTrendCtx, {
                type: 'line',
                data: {
                    labels: {!! json_encode(array_keys($commitsChartData)) !!},
                    datasets: [{
                        label: 'Commits',
                        data: {!! json_encode(array_values($commitsChartData)) !!},
                        borderColor: '#0ea5e9',
                        backgroundColor: 'rgba(14, 165, 233, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.3,
                        pointRadius: 4,
                        pointBackgroundColor: '#0ea5e9'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(148, 163, 184, 0.08)' },
                            ticks: { color: '#64748b', font: { size: 10 } }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#64748b', font: { size: 10 } }
                        }
                    }
                }
            });
        }

        // 2. MR Distribution Chart
        const mrDistCtx = document.getElementById('mrDistributionChart');
        if (mrDistCtx) {
            new Chart(mrDistCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Opened', 'Merged', 'Approved', 'Rejected'],
                    datasets: [{
                        data: [
                            {{ $mrDistribution['Opened'] }},
                            {{ $mrDistribution['Merged'] }},
                            {{ $mrDistribution['Approved'] }},
                            {{ $mrDistribution['Rejected'] }}
                        ],
                        backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444'],
                        borderWidth: 2,
                        borderColor: 'transparent'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    cutout: '70%'
                }
            });
        }
    });
</script>
@endsection
