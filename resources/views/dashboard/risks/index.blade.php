@extends('layouts.app')

@section('content')
<div x-data="riskDashboard()" class="space-y-8 animate-fade-in pb-16">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Predictive Risk Center</h2>
            <!-- <p class="text-sm text-slate-500 dark:text-slate-400">Monitor your team's risk levels with real-time AI insights.   </p> -->
        </div>
        <div class="px-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse" :class="loading ? 'bg-amber-500' : 'bg-emerald-500'"></span>
            <span class="text-xs font-semibold text-slate-600 dark:text-slate-300" x-text="loading ? 'Syncing data...' : 'Live Telemetry Active'">Live Telemetry Active</span>
        </div>
    </div>

    <!-- Summary Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
        <!-- High Risks -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm flex items-center justify-between border-l-4 border-l-red-500 hover:shadow-md transition-shadow">
            <div class="space-y-1">
                <span class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">High Risks</span>
                <span class="text-3xl font-extrabold text-slate-900 dark:text-white" x-text="stats.high">0</span>
            </div>
            <div class="p-3 bg-red-50 dark:bg-red-950/20 text-red-500 dark:text-red-400 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
        </div>

        <!-- Medium Risks -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm flex items-center justify-between border-l-4 border-l-amber-500 hover:shadow-md transition-shadow">
            <div class="space-y-1">
                <span class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Medium Risks</span>
                <span class="text-3xl font-extrabold text-slate-900 dark:text-white" x-text="stats.medium">0</span>
            </div>
            <div class="p-3 bg-amber-50 dark:bg-amber-950/20 text-amber-500 dark:text-amber-400 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>

        <!-- Low Risks -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm flex items-center justify-between border-l-4 border-l-emerald-500 hover:shadow-md transition-shadow">
            <div class="space-y-1">
                <span class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Low Risks</span>
                <span class="text-3xl font-extrabold text-slate-900 dark:text-white" x-text="stats.low">0</span>
            </div>
            <div class="p-3 bg-emerald-50 dark:bg-emerald-950/20 text-emerald-500 dark:text-emerald-400 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>

        <!-- Resolved Risks -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm flex items-center justify-between border-l-4 border-l-slate-400 hover:shadow-md transition-shadow">
            <div class="space-y-1">
                <span class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Resolved</span>
                <span class="text-3xl font-extrabold text-slate-900 dark:text-white" x-text="stats.resolved">0</span>
            </div>
            <div class="p-3 bg-slate-50 dark:bg-slate-850 text-slate-500 dark:text-slate-400 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </div>
        </div>

        <!-- Team Health Score -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow"
             :class="{
                 'border-l-4 border-l-red-500': health.level === 'red',
                 'border-l-4 border-l-amber-500': health.level === 'yellow',
                 'border-l-4 border-l-emerald-500': health.level === 'green'
             }">
            <div class="flex items-center justify-between">
                <span class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Team Health</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold border uppercase tracking-wider"
                      :class="{
                          'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/20 dark:text-red-400 dark:border-red-900': health.level === 'red',
                          'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900': health.level === 'yellow',
                          'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900': health.level === 'green'
                      }"
                      x-text="health.status_text">Excellent</span>
            </div>
            <div class="flex items-baseline gap-2 mt-2">
                <span class="text-3xl font-extrabold text-slate-900 dark:text-white" x-text="health.current_score">0.0</span>
                <span class="text-xs text-slate-400 font-medium">/10</span>
            </div>
            <div class="flex items-center gap-1 mt-1 text-[11px]">
                <svg x-show="health.trend === 'up'" class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                <svg x-show="health.trend === 'down'" class="w-3.5 h-3.5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                <svg x-show="health.trend === 'flat'" class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 12h14"></path></svg>
                
                <span class="font-bold"
                      :class="{
                          'text-emerald-500': health.trend === 'up',
                          'text-red-500': health.trend === 'down',
                          'text-slate-400': health.trend === 'flat'
                      }"
                      x-text="health.difference > 0 ? '+' + health.difference : health.difference">0.0</span>
                <span class="text-slate-400 font-medium">vs last week</span>
            </div>
        </div>
    </div>

    <!-- Visual Analytics Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Trend Chart -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm lg:col-span-2 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Risk Detection Trends</h3>
                
                <!-- Period Switcher -->
                <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-0.5 rounded-xl border border-slate-200/40 dark:border-slate-700/45">
                    <button @click="trendPeriod = 'daily'" :class="trendPeriod === 'daily' ? 'bg-white dark:bg-slate-900 shadow-sm text-skyAccent dark:text-blue-400 font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 font-semibold'" class="px-3 py-1.5 text-[11px] rounded-lg transition-all">Daily</button>
                    <button @click="trendPeriod = 'weekly'" :class="trendPeriod === 'weekly' ? 'bg-white dark:bg-slate-900 shadow-sm text-skyAccent dark:text-blue-400 font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 font-semibold'" class="px-3 py-1.5 text-[11px] rounded-lg transition-all">Weekly</button>
                    <button @click="trendPeriod = 'monthly'" :class="trendPeriod === 'monthly' ? 'bg-white dark:bg-slate-900 shadow-sm text-skyAccent dark:text-blue-400 font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 font-semibold'" class="px-3 py-1.5 text-[11px] rounded-lg transition-all">Monthly</button>
                </div>
            </div>
            
            <div class="h-64 relative w-full">
                <canvas id="riskTrendChart"></canvas>
            </div>
        </div>

        <!-- Distribution Chart -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm flex flex-col justify-between">
            <div class="border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Active Severity Breakdown</h3>
            </div>
            
            <div class="relative w-full h-48 flex items-center justify-center my-4">
                <canvas id="riskDistributionChart"></canvas>
                <!-- Center Text Overlay -->
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <span class="text-3xl font-extrabold text-slate-900 dark:text-white" x-text="stats.high + stats.medium + stats.low">0</span>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Active Risks</span>
                </div>
            </div>
            
            <div class="flex justify-around text-xs font-semibold text-slate-500 border-t border-slate-100 dark:border-slate-800 pt-3">
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> High</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Medium</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Low</span>
            </div>
        </div>
    </div>

    <!-- AI Insights & Risk Management Center -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Risk Alerts List and Filters -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm lg:col-span-2 space-y-6">
            <!-- Header with Tab Navigation -->
            <div class="flex flex-col gap-4 border-b border-slate-100 dark:border-slate-800 pb-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Risk Action Center</h3>
                    
                    <!-- Tabs -->
                    <nav class="flex flex-wrap gap-1 bg-slate-100 dark:bg-slate-800 p-0.5 rounded-xl border border-slate-200/40 dark:border-slate-700/45">
                        <button @click="activeTab = 'all'" :class="activeTab === 'all' ? 'bg-white dark:bg-slate-900 shadow-sm text-skyAccent dark:text-blue-400 font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-250'" class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all">All Active</button>
                        <button @click="activeTab = 'high'" :class="activeTab === 'high' ? 'bg-white dark:bg-slate-900 shadow-sm text-red-650 dark:text-red-400 font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-red-500'" class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all">High</button>
                        <button @click="activeTab = 'medium'" :class="activeTab === 'medium' ? 'bg-white dark:bg-slate-900 shadow-sm text-amber-600 dark:text-amber-450 font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-amber-500'" class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all">Medium</button>
                        <button @click="activeTab = 'low'" :class="activeTab === 'low' ? 'bg-white dark:bg-slate-900 shadow-sm text-emerald-600 dark:text-emerald-450 font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-emerald-500'" class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all">Low</button>
                        <button @click="activeTab = 'resolved'" :class="activeTab === 'resolved' ? 'bg-white dark:bg-slate-900 shadow-sm text-slate-700 dark:text-slate-300 font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'" class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all">Resolved</button>
                    </nav>
                </div>

                <!-- Advanced Filters header -->
                <div class="flex items-center justify-between mt-2">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                        Combinable Filters
                    </span>
                    <button @click="clearFilters()" class="text-xs font-bold text-skyAccent hover:text-sky-600 dark:text-blue-450 dark:hover:text-blue-300 transition-colors">
                        Reset Filters
                    </button>
                </div>

                <!-- Filters Grid -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-4 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-slate-200/50 dark:border-slate-800/80">
                    <!-- Name Search -->
                    <div class="space-y-1">
                        <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Employee Name</label>
                        <input type="text" x-model.debounce.300ms="filters.employee" placeholder="Search employee..." 
                               class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-skyAccent">
                    </div>
                    <!-- Type Filter -->
                    <div class="space-y-1">
                        <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Risk Type</label>
                        <select x-model="filters.risk_type" 
                                class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-skyAccent">
                            <option value="all">All Types</option>
                            <option value="burnout">Burnout Risk</option>
                            <option value="deadline">Deadline Risk</option>
                            <option value="performance">Performance Decline</option>
                            <option value="attendance">Attendance Risk</option>
                            <option value="inactivity">Inactivity Risk</option>
                            <option value="productivity">Productivity Decline</option>
                            <option value="overload">Task Overload</option>
                            <option value="dependency">Team Dependency</option>
                        </select>
                    </div>
                    <!-- Severity Filter -->
                    <div class="space-y-1">
                        <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Severity</label>
                        <select x-model="filters.risk_severity" 
                                class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-skyAccent">
                            <option value="all">All Severities</option>
                            <option value="high">High Risk</option>
                            <option value="medium">Medium Risk</option>
                            <option value="low">Low Risk</option>
                        </select>
                    </div>
                    <!-- Date Picker -->
                    <div class="space-y-1">
                        <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Start Date</label>
                        <input type="date" x-model="filters.start_date" 
                               class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-2 py-2 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-skyAccent">
                    </div>
                </div>
            </div>

            <!-- Loader / List Container -->
            <div class="relative min-h-[300px]">
                <!-- Loading State Skeleton -->
                <div x-show="loading" class="space-y-4">
                    <template x-for="i in 3" :key="i">
                        <div class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800/80 rounded-2xl p-6 space-y-4 animate-pulse">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-800"></div>
                                <div class="flex-1 space-y-2">
                                    <div class="h-4 bg-slate-100 dark:bg-slate-800 rounded w-1/4"></div>
                                    <div class="h-3 bg-slate-100 dark:bg-slate-800 rounded w-1/2"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Actual Risks Cards -->
                <div x-show="!loading" class="space-y-4">
                    <template x-for="risk in risksData.data" :key="risk.id">
                        <div @click="openDetail(risk)"
                             class="bg-slate-50 hover:bg-white dark:bg-slate-900 border border-slate-100 hover:border-slate-250 dark:border-slate-850 dark:hover:border-slate-750 rounded-2xl shadow-sm hover:shadow-md p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-6 cursor-pointer transition-all hover:scale-[1.005]">
                            
                            <div class="flex items-start gap-4 flex-1">
                                <!-- Dynamic Icon container based on Risk Type -->
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 border"
                                     :class="{
                                         'bg-red-50 dark:bg-red-950/20 border-red-200 dark:border-red-800 text-red-655 dark:text-red-400': risk.risk_level === 'high' && !risk.is_resolved,
                                         'bg-amber-50 dark:bg-amber-950/20 border-amber-200 dark:border-amber-800 text-amber-600 dark:text-amber-450': risk.risk_level === 'medium' && !risk.is_resolved,
                                         'bg-emerald-50 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-800 text-emerald-600 dark:text-emerald-450': risk.risk_level === 'low' && !risk.is_resolved,
                                         'bg-slate-100 dark:bg-slate-800/40 border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400': risk.is_resolved
                                     }">
                                    
                                    <!-- Flame icon for burnout -->
                                    <template x-if="risk.risk_type === 'burnout'">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"></path></svg>
                                    </template>
                                    <!-- Clock for deadline -->
                                    <template x-if="risk.risk_type === 'deadline'">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </template>
                                    <!-- Trend down for performance -->
                                    <template x-if="risk.risk_type === 'performance'">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                                    </template>
                                    <!-- Clipboard check/x for attendance -->
                                    <template x-if="risk.risk_type === 'attendance'">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-4 7h4m-4 4h4m-1-4h1m-1 4h1"></path></svg>
                                    </template>
                                    <!-- Alarm Clock for inactivity -->
                                    <template x-if="risk.risk_type === 'inactivity'">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                    </template>
                                    <!-- Target / Progress decline for productivity -->
                                    <template x-if="risk.risk_type === 'productivity'">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    </template>
                                    <!-- Stack for overload -->
                                    <template x-if="risk.risk_type === 'overload'">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                    </template>
                                    <!-- Connection/Nodes for dependency -->
                                    <template x-if="risk.risk_type === 'dependency'">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                    </template>
                                </div>

                                <div class="space-y-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h4 class="font-bold text-slate-800 dark:text-slate-100" x-text="risk.employee ? risk.employee.name : 'Unknown Employee'"></h4>
                                        
                                        <!-- Severity Badges -->
                                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase tracking-wide border"
                                              :class="{
                                                  'bg-red-100 text-red-800 border-red-200 dark:bg-red-950/40 dark:text-red-400 dark:border-red-900': risk.risk_level === 'high' && !risk.is_resolved,
                                                  'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-900': risk.risk_level === 'medium' && !risk.is_resolved,
                                                  'bg-emerald-100 text-emerald-850 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900': risk.risk_level === 'low' && !risk.is_resolved,
                                                  'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700': risk.is_resolved
                                              }"
                                              x-text="risk.is_resolved ? 'resolved' : risk.risk_level">
                                        </span>

                                        <!-- Type Badge -->
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide bg-slate-100 dark:bg-slate-800/80 text-slate-600 dark:text-slate-450 border border-slate-200 dark:border-slate-700"
                                              x-text="getRiskTypeLabel(risk.risk_type)">
                                        </span>

                                        <!-- Confidence Score Badge -->
                                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-sky-50 dark:bg-sky-950/20 text-skyAccent dark:text-blue-400 border border-sky-100/60 dark:border-blue-900/30"
                                              x-text="Math.round(risk.confidence_score * 100) + '% AI confidence'">
                                        </span>
                                    </div>
                                    
                                    <p class="text-sm text-slate-600 dark:text-slate-350 font-medium" x-text="risk.reason"></p>
                                    
                                    <!-- Sub-details: timestamps and notes/action badges -->
                                    <div class="flex items-center gap-4 text-[11px] text-slate-400 pt-1 font-medium">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            <span x-text="risk.time_ago"></span>
                                        </span>
                                        <span x-show="risk.manager_notes" class="inline-flex items-center gap-1 text-slate-500 dark:text-slate-400">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            Notes Saved
                                        </span>
                                        <span x-show="risk.follow_up_action" class="inline-flex items-center gap-1 text-indigo-500 dark:text-indigo-400 font-bold">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-4 7h4m-4 4h4m-1-4h1m-1 4h1"></path></svg>
                                            Follow-up Pending
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Review Action -->
                            <div class="shrink-0 w-full md:w-auto flex justify-end">
                                <button type="button" @click.stop="openDetail(risk)"
                                        class="px-4 py-2 bg-slate-100 hover:bg-skyAccent hover:text-white dark:bg-slate-800 dark:hover:bg-blue-500 text-slate-700 dark:text-slate-350 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 hover:border-transparent dark:hover:border-transparent transition-all">
                                    Review & Action
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- Empty State -->
                    <div x-show="risksData.data.length === 0" 
                         class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-10 text-center space-y-3 shadow-sm">
                        <div class="w-12 h-12 rounded-full bg-emerald-50 dark:bg-emerald-950/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto animate-pulse">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-900 dark:text-white">All Clear!</h4>
                            <p class="text-sm text-slate-500 dark:text-slate-400">No active risks match your filters currently.</p>
                        </div>
                    </div>

                    <!-- Custom Clean Pagination -->
                    <div x-show="risksData.last_page > 1" class="flex items-center justify-between border-t border-slate-100 dark:border-slate-800 pt-4 px-2">
                        <div class="text-xs text-slate-400">
                            Showing <span class="font-bold text-slate-700 dark:text-slate-300" x-text="risksData.from"></span> to <span class="font-bold text-slate-700 dark:text-slate-300" x-text="risksData.to"></span> of <span class="font-bold text-slate-700 dark:text-slate-300" x-text="risksData.total"></span> alerts
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="risksData.current_page > 1 ? fetchData(risksData.current_page - 1) : null"
                                    :disabled="risksData.current_page <= 1"
                                    class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-xs font-bold bg-white dark:bg-slate-900 text-slate-655 dark:text-slate-350 disabled:opacity-50 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                Previous
                            </button>
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-3 py-1.5 rounded-lg">
                                Page <span x-text="risksData.current_page"></span> of <span x-text="risksData.last_page"></span>
                            </span>
                            <button @click="risksData.current_page < risksData.last_page ? fetchData(risksData.current_page + 1) : null"
                                    :disabled="risksData.current_page >= risksData.last_page"
                                    class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-xs font-bold bg-white dark:bg-slate-900 text-slate-655 dark:text-slate-350 disabled:opacity-50 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Insights, Recommendations & Telemetry Panel -->
        <div class="space-y-6">
            <!-- Insights Panel -->
            <div class="bg-gradient-to-br from-slate-900 to-indigo-950 dark:from-slate-900 dark:to-slate-950 border border-slate-800 p-6 rounded-2xl shadow-xl text-white space-y-6 relative overflow-hidden">
                <!-- AI sparkles abstract bg overlay -->
                <div class="absolute -right-12 -top-12 w-32 h-32 bg-skyAccent/10 rounded-full blur-2xl pointer-events-none"></div>
                
                <div class="border-b border-white/10 pb-4 flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-skyAccent/20 flex items-center justify-center text-skyAccent">
                        <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364.364l-.707.707M21 12h-1M4 9H3m15.364 6.364l-.707-.707M6.343 6.343l.707-.707m9.9 5.05a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-sm tracking-wide uppercase text-slate-200">AI Intelligence Insights</h3>
                        <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">Automated Heuristic Engine</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <template x-for="(insight, index) in ai.insights" :key="index">
                        <div class="flex items-start gap-3">
                            <span class="w-1.5 h-1.5 rounded-full bg-skyAccent mt-2 shrink-0"></span>
                            <p class="text-xs text-slate-300 leading-relaxed font-medium" x-text="insight"></p>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Recommendations list -->
            <div class="space-y-4">
                <div class="flex items-center justify-between px-2">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Recommended Actions</span>
                    <span class="px-2 py-0.5 rounded-full bg-sky-50 dark:bg-sky-950/20 text-skyAccent dark:text-blue-400 text-[10px] font-extrabold" x-text="ai.recommendations.length + ' tasks'"></span>
                </div>

                <div class="space-y-4">
                    <template x-for="rec in ai.recommendations" :key="rec.id">
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm space-y-3 hover:shadow-md transition-shadow">
                            <div>
                                <h4 class="text-xs font-extrabold text-slate-800 dark:text-slate-200" x-text="rec.title"></h4>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 leading-relaxed" x-text="rec.description"></p>
                            </div>
                            <div class="flex justify-end">
                                <button type="button" @click="triggerRecommendation(rec)"
                                        class="px-3.5 py-2 bg-sky-50 dark:bg-sky-955/20 hover:bg-skyAccent hover:text-white text-skyAccent dark:text-blue-450 dark:hover:bg-blue-600 dark:hover:text-white border border-sky-100 dark:border-blue-900/30 hover:border-transparent dark:hover:border-transparent text-[11px] font-bold rounded-xl transition-all">
                                    <span x-text="rec.action"></span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Slide-over panel container -->
    <div x-show="slideOverOpen" class="fixed inset-0 z-50 overflow-hidden" style="display: none;" x-cloak>
        <div class="absolute inset-0 overflow-hidden">
            <!-- Backdrop overlay -->
            <div x-show="slideOverOpen" 
                 x-transition:enter="ease-in-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in-out duration-300"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" 
                 @click="slideOverOpen = false"></div>
            
            <!-- Drawer slider container -->
            <div class="fixed inset-y-0 right-0 pl-10 max-w-full flex">
                <div x-show="slideOverOpen" 
                     x-transition:enter="transform transition ease-in-out duration-300 sm:duration-400"
                     x-transition:enter-start="translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transform transition ease-in-out duration-300 sm:duration-400"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="translate-x-full"
                     class="w-screen max-w-md">
                    
                    <div class="h-full flex flex-col bg-white dark:bg-slate-900 shadow-2xl border-l border-slate-200 dark:border-slate-800">
                        <!-- Drawer Header -->
                        <div class="px-6 py-5 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-sky-50 dark:bg-sky-950/20 text-skyAccent dark:text-sky-400 flex items-center justify-center font-bold text-sm"
                                     x-text="selectedRisk && selectedRisk.employee ? selectedRisk.employee.name.substring(0, 2).toUpperCase() : 'EE'">
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-900 dark:text-white" x-text="selectedRisk && selectedRisk.employee ? selectedRisk.employee.name : 'Risk Details'"></h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-400" x-text="selectedRisk && selectedRisk.employee ? selectedRisk.employee.email : ''"></p>
                                </div>
                            </div>
                            <button @click="slideOverOpen = false" class="text-slate-450 hover:text-slate-600 dark:hover:text-slate-350 focus:outline-none">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        
                        <!-- Drawer Content (Scrollable) -->
                        <div class="flex-1 overflow-y-auto p-6 space-y-6">
                            <!-- Severity & Status -->
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Severity Level</span>
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold border uppercase tracking-wider"
                                          :class="{
                                              'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/20 dark:text-red-400 dark:border-red-900': selectedRisk && selectedRisk.risk_level === 'high' && !selectedRisk.is_resolved,
                                              'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900': selectedRisk && selectedRisk.risk_level === 'medium' && !selectedRisk.is_resolved,
                                              'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900': selectedRisk && selectedRisk.risk_level === 'low' && !selectedRisk.is_resolved,
                                              'bg-slate-50 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700': selectedRisk && selectedRisk.is_resolved
                                          }"
                                          x-text="selectedRisk && selectedRisk.is_resolved ? 'resolved' : (selectedRisk ? selectedRisk.risk_level : '')">
                                    </span>
                                </div>
                                
                                <!-- Core Telemetry details -->
                                <div class="grid grid-cols-2 gap-4 bg-slate-50 dark:bg-slate-800/40 p-4 rounded-2xl border border-slate-200/50 dark:border-slate-800/80">
                                    <div>
                                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Telemetry Type</span>
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300" x-text="selectedRisk ? getRiskTypeLabel(selectedRisk.risk_type) : ''"></span>
                                    </div>
                                    <div>
                                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">AI Confidence</span>
                                        <span class="text-xs font-bold text-skyAccent dark:text-blue-400" x-text="selectedRisk ? Math.round(selectedRisk.confidence_score * 100) + '%' : ''"></span>
                                    </div>
                                    <div class="col-span-2">
                                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Detection Timestamp</span>
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300" x-text="selectedRisk ? selectedRisk.formatted_date : ''"></span>
                                    </div>
                                </div>
                                
                                <div class="space-y-1">
                                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Detection Reason</span>
                                    <p class="text-xs leading-relaxed font-semibold text-slate-600 dark:text-slate-300" x-text="selectedRisk ? selectedRisk.reason : ''"></p>
                                </div>

                                <!-- Direct Link to employee profile -->
                                <div x-show="selectedRisk" class="pt-2">
                                    <a :href="'/dashboard/employees/' + (selectedRisk ? selectedRisk.employee_id : '')"
                                       class="inline-flex items-center gap-2 text-xs font-bold text-skyAccent hover:text-sky-600 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                                        View supervisor workspace profile
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                    </a>
                                </div>
                            </div>
                            
                            <hr class="border-slate-100 dark:border-slate-800">
                            
                            <!-- Note Editor -->
                            <div class="space-y-3">
                                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-2">
                                    <svg class="w-4 h-4 text-slate-450" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    Manager Audit Notes
                                </h4>
                                <textarea x-model="newNote" rows="3" placeholder="Add manager observations, details or reasoning logs..."
                                          class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-slate-100 placeholder-slate-450 focus:outline-none focus:ring-1 focus:ring-skyAccent"></textarea>
                                <div class="flex justify-end">
                                    <button type="button" @click="submitNote()" :disabled="notesSaving"
                                            class="px-3.5 py-2 bg-skyAccent hover:bg-sky-600 disabled:opacity-50 text-white text-xs font-bold rounded-lg transition-all flex items-center gap-1.5">
                                        <span x-show="notesSaving" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                        Save Audit Notes
                                    </button>
                                </div>
                            </div>
                            
                            <hr class="border-slate-100 dark:border-slate-800">
                            
                            <!-- Follow-up tasks -->
                            <div class="space-y-3">
                                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-2">
                                    <svg class="w-4 h-4 text-slate-450" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-4 7h4m-4 4h4m-1-4h1m-1 4h1"></path></svg>
                                    Assign Follow-up Action
                                </h4>
                                <input type="text" x-model="newFollowUp" placeholder="e.g. Schedule workload adjustment session..."
                                       class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-slate-100 placeholder-slate-450 focus:outline-none focus:ring-1 focus:ring-skyAccent">
                                <div class="flex justify-end">
                                    <button type="button" @click="submitFollowUp()" :disabled="followUpSaving"
                                            class="px-3.5 py-2 bg-indigo-500 hover:bg-indigo-600 disabled:opacity-50 text-white text-xs font-bold rounded-lg transition-all flex items-center gap-1.5">
                                        <span x-show="followUpSaving" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                        Assign Action
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Resolution Action -->
                        <div x-show="selectedRisk && !selectedRisk.is_resolved"
                             class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-800">
                            <button type="button" @click="resolveRisk()" :disabled="resolving"
                                    class="w-full py-3 bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-white text-xs font-bold rounded-xl shadow-sm transition-all flex items-center justify-center gap-2">
                                <span x-show="resolving" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                <span>Mark Alert Resolved</span>
                            </button>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Load Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
function riskDashboard() {
    return {
        activeTab: 'all',
        filters: {
            employee: '',
            risk_type: 'all',
            risk_severity: 'all',
            start_date: '',
            end_date: ''
        },
        trendPeriod: 'daily',
        risksData: {
            data: [],
            current_page: 1,
            last_page: 1,
            total: 0,
            from: 0,
            to: 0,
            per_page: 15
        },
        stats: {
            high: 0,
            medium: 0,
            low: 0,
            resolved: 0
        },
        health: {
            current_score: 0.0,
            previous_score: 0.0,
            difference: 0.0,
            trend: 'flat',
            level: 'green',
            status_text: 'Excellent'
        },
        ai: {
            insights: [],
            recommendations: []
        },
        trends: {
            labels: [],
            data: []
        },
        loading: false,
        selectedRisk: null,
        slideOverOpen: false,
        newNote: '',
        newFollowUp: '',
        notesSaving: false,
        followUpSaving: false,
        resolving: false,

        // Chart instances
        doughnutChart: null,
        lineChart: null,

        init() {
            this.fetchData();

            // Set up watchers to reload on change
            this.$watch('filters', () => {
                this.risksData.current_page = 1;
                this.fetchData();
            }, { deep: true });

            this.$watch('activeTab', () => {
                this.risksData.current_page = 1;
                this.fetchData();
            });

            this.$watch('trendPeriod', () => {
                this.fetchData();
            });

            // Watch dark mode class on HTML using MutationObserver
            const observer = new MutationObserver(() => {
                this.updateCharts();
            });
            observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        },

        clearFilters() {
            this.filters.employee = '';
            this.filters.risk_type = 'all';
            this.filters.risk_severity = 'all';
            this.filters.start_date = '';
            this.filters.end_date = '';
        },

        async fetchData(page = 1) {
            this.loading = true;
            try {
                let params = new URLSearchParams();
                params.append('page', page);
                params.append('tab', this.activeTab);
                params.append('employee', this.filters.employee);
                params.append('risk_type', this.filters.risk_type);
                params.append('risk_severity', this.filters.risk_severity);
                params.append('start_date', this.filters.start_date);
                params.append('end_date', this.filters.end_date);
                params.append('trend_period', this.trendPeriod);

                const response = await fetch(`/dashboard/risks/data?${params.toString()}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const result = await response.json();
                    this.stats = result.stats;
                    this.health = result.health;
                    this.ai = result.ai;
                    this.trends = result.trends;
                    this.risksData = result.risks;

                    // Sync details if currently selected
                    if (this.selectedRisk) {
                        const updated = this.risksData.data.find(r => r.id === this.selectedRisk.id);
                        if (updated) {
                            this.selectedRisk = updated;
                        }
                    }

                    this.updateCharts();
                }
            } catch (e) {
                console.error("Failed to load dashboard telemetry:", e);
            } finally {
                this.loading = false;
            }
        },

        async submitNote() {
            if (!this.selectedRisk || !this.newNote.trim()) return;
            this.notesSaving = true;
            try {
                const response = await fetch(`/dashboard/risks/${this.selectedRisk.id}/notes`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ manager_notes: this.newNote })
                });

                if (response.ok) {
                    this.selectedRisk.manager_notes = this.newNote;
                    this.newNote = '';
                    await this.fetchData(this.risksData.current_page);
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.notesSaving = false;
            }
        },

        async submitFollowUp() {
            if (!this.selectedRisk || !this.newFollowUp.trim()) return;
            this.followUpSaving = true;
            try {
                const response = await fetch(`/dashboard/risks/${this.selectedRisk.id}/follow-up`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ follow_up_action: this.newFollowUp })
                });

                if (response.ok) {
                    this.selectedRisk.follow_up_action = this.newFollowUp;
                    this.newFollowUp = '';
                    await this.fetchData(this.risksData.current_page);
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.followUpSaving = false;
            }
        },

        async resolveRisk(id = null) {
            const targetId = id || (this.selectedRisk ? this.selectedRisk.id : null);
            if (!targetId) return;

            this.resolving = true;
            try {
                const response = await fetch(`/dashboard/risks/${targetId}/resolve`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    if (this.selectedRisk && this.selectedRisk.id === targetId) {
                        this.selectedRisk.is_resolved = true;
                        this.slideOverOpen = false;
                    }
                    await this.fetchData(this.risksData.current_page);
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.resolving = false;
            }
        },

        openDetail(risk) {
            this.selectedRisk = risk;
            this.newNote = risk.manager_notes || '';
            this.newFollowUp = risk.follow_up_action || '';
            this.slideOverOpen = true;
        },

        triggerRecommendation(rec) {
            if (!rec.risk_id) {
                // Navigate to helper routes
                if (rec.action === 'Schedule Call') {
                    window.location.href = '/dashboard/teams';
                } else if (rec.action === 'Analyze Workload') {
                    window.location.href = '/dashboard/workload';
                } else {
                    window.location.href = '/dashboard/tasks';
                }
                return;
            }

            // Find risk in list
            const risk = this.risksData.data.find(r => r.id === rec.risk_id);
            if (risk) {
                this.openDetail(risk);
            } else {
                // Pull from active list tab
                this.activeTab = 'all';
                this.clearFilters();
                this.fetchData().then(() => {
                    const target = this.risksData.data.find(r => r.id === rec.risk_id);
                    if (target) {
                        this.openDetail(target);
                    }
                });
            }
        },

        getRiskTypeLabel(type) {
            const labels = {
                burnout: 'Burnout Risk',
                deadline: 'Deadline Risk',
                performance: 'Performance Decline',
                attendance: 'Attendance Risk',
                inactivity: 'Inactivity Risk',
                productivity: 'Productivity Decline',
                overload: 'Task Overload',
                dependency: 'Team Dependency'
            };
            return labels[type] || type;
        },

        getThemeColors() {
            const isDark = document.documentElement.classList.contains('dark');
            return {
                text: isDark ? '#94a3b8' : '#475569',
                grid: isDark ? 'rgba(148, 163, 184, 0.05)' : 'rgba(148, 163, 184, 0.1)',
                tooltipBg: isDark ? '#1e293b' : '#0f172a'
            };
        },

        updateCharts() {
            this.renderDoughnutChart();
            this.renderLineChart();
        },

        renderDoughnutChart() {
            const ctx = document.getElementById('riskDistributionChart');
            if (!ctx) return;

            const counts = [this.stats.high, this.stats.medium, this.stats.low];
            
            if (this.doughnutChart) {
                this.doughnutChart.data.datasets[0].data = counts;
                this.doughnutChart.options.plugins.legend.labels.color = this.getThemeColors().text;
                this.doughnutChart.update();
            } else {
                this.doughnutChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['High', 'Medium', 'Low'],
                        datasets: [{
                            data: counts,
                            backgroundColor: ['#ef4444', '#f59e0b', '#10b981'],
                            hoverOffset: 6,
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '72%',
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: this.getThemeColors().tooltipBg,
                                titleFont: { family: 'Plus Jakarta Sans', weight: 'bold' },
                                bodyFont: { family: 'Plus Jakarta Sans' },
                                cornerRadius: 10,
                                padding: 10
                            }
                        }
                    }
                });
            }
        },

        renderLineChart() {
            const ctx = document.getElementById('riskTrendChart');
            if (!ctx) return;

            const theme = this.getThemeColors();

            if (this.lineChart) {
                this.lineChart.data.labels = this.trends.labels;
                this.lineChart.data.datasets[0].data = this.trends.data;
                this.lineChart.options.scales.y.grid.color = theme.grid;
                this.lineChart.options.scales.y.ticks.color = theme.text;
                this.lineChart.options.scales.x.ticks.color = theme.text;
                this.lineChart.update();
            } else {
                this.lineChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: this.trends.labels,
                        datasets: [{
                            label: 'Risk Alerts Detected',
                            data: this.trends.data,
                            borderColor: '#0ea5e9',
                            backgroundColor: 'rgba(14, 165, 233, 0.06)',
                            borderWidth: 2.5,
                            tension: 0.3,
                            fill: true,
                            pointBackgroundColor: '#0ea5e9',
                            pointHoverRadius: 6,
                            pointHoverBorderWidth: 3,
                            pointHoverBackgroundColor: '#fff',
                            pointHoverBorderColor: '#0ea5e9'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: theme.tooltipBg,
                                titleFont: { family: 'Plus Jakarta Sans', weight: 'bold', size: 12 },
                                bodyFont: { family: 'Plus Jakarta Sans', size: 12 },
                                cornerRadius: 10,
                                padding: 10,
                                displayColors: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: theme.grid,
                                    drawBorder: false
                                },
                                ticks: {
                                    color: theme.text,
                                    font: { family: 'Plus Jakarta Sans', weight: 500 },
                                    stepSize: 1
                                }
                            },
                            x: {
                                grid: { display: false },
                                ticks: {
                                    color: theme.text,
                                    font: { family: 'Plus Jakarta Sans', weight: 500 }
                                }
                            }
                        }
                    }
                });
            }
        }
    };
}
</script>
@endsection
