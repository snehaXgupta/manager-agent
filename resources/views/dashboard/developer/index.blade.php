@extends('layouts.app')

@section('content')
<!-- Full-width and full-height split pane layout extending to container edges -->
<div class="flex-1 flex flex-col lg:flex-row -mx-6 -mb-6 md:-mx-8 md:-mb-8 {{ session('success') || session('error') ? 'mt-6' : '-mt-6 md:-mt-8' }} min-h-[calc(100vh-80px)] overflow-x-hidden"
     x-data="{ activeLang: 'CURL' }">
    
    <!-- LEFT PANEL: Access Tokens Manager (Light Pane) -->
    <div class="flex-1 bg-white dark:bg-slate-900 p-6 md:p-8 border-r border-slate-200 dark:border-slate-800 flex flex-col space-y-6">
        
        <!-- Header -->
        <div class="space-y-1">
            <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Developer Tools</h2>
            <p class="text-sm text-slate-505 dark:text-slate-400">Integrate ManagerAgent metrics, timers, and alerts into your external pipelines and workflows.</p>
        </div>

        <!-- Fireflies Connection Diagnostic Link -->
        <div class="p-4 bg-slate-50 dark:bg-slate-805/30 border border-slate-150 dark:border-slate-800 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-purple-50 dark:bg-purple-950/20 text-purple-650 dark:text-purple-400 rounded-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                </div>
                <div>
                    <span class="block text-sm font-bold text-slate-850 dark:text-white">Fireflies AI Integration</span>
                    <span class="block text-[11px] text-slate-450 mt-0.5">Test API credentials, trigger manual syncing, and inspect diagnostic status metrics.</span>
                </div>
            </div>
            <a href="{{ route('dashboard.fireflies-test') }}" class="px-3.5 py-2 bg-purple-650 hover:bg-purple-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm whitespace-nowrap shrink-0">
                Diagnostic Panel
            </a>
        </div>

        <!-- Banner with Purple/Violet Gradient -->
        <div class="p-6 bg-gradient-to-r from-violet-600 to-indigo-600 rounded-2xl text-white flex flex-col md:flex-row justify-between items-start md:items-center gap-4 shadow-md">
            <div>
                <h3 class="text-lg font-bold">Access Tokens</h3>
                <div class="flex items-center gap-1.5 text-xs text-violet-100 mt-1">
                    <!-- Info Circle Icon -->
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                    <span>Create & manage your api keys</span>
                </div>
            </div>
            <form action="{{ route('dashboard.developer.tokens.store') }}" method="POST" class="m-0 shrink-0">
                @csrf
                <button type="submit" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-semibold rounded-xl transition-all shadow-md transform hover:-translate-y-0.5 active:translate-y-0">
                    Generate API key
                </button>
            </form>
        </div>

        <!-- Tokens Listing Table -->
        <div class="overflow-x-auto border border-slate-150 dark:border-slate-800/60 rounded-xl">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-150 dark:border-slate-800 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase bg-slate-50 dark:bg-slate-900/40">
                        <th class="px-6 py-4">Token</th>
                        <th class="px-6 py-4">Created On</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-150 dark:divide-slate-800/50 text-sm">
                    @forelse($tokens as $token)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                            <td class="px-6 py-4">
                                <!-- Eye toggle & Copy wrapper -->
                                <div x-data="{ show: false, copied: false }" class="flex items-center gap-2">
                                    <span class="font-mono text-xs text-slate-600 dark:text-slate-400 tracking-wider bg-slate-100 dark:bg-slate-800/80 px-2.5 py-1 rounded" 
                                          x-text="show ? '{{ $token->raw_token }}' : '••••••••••••••••••••••••••••••••••••••••'"></span>
                                    
                                    <!-- Toggle Visibility Button -->
                                    <button @click="show = !show" type="button" class="text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300 transition-colors p-1" title="Toggle Visibility">
                                        <template x-if="!show">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        </template>
                                        <template x-if="show">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 5.656m0 0l-8.228-8.228m11.272 11.272L12 18.5c-4.478 0-8.268-2.943-9.542-7a19.97 19.97 0 011.64-4.5m1.528-1.528l11.272 11.272z"></path></svg>
                                        </template>
                                    </button>

                                    <!-- Copy Button -->
                                    <div class="relative">
                                        <button @click="navigator.clipboard.writeText('{{ $token->raw_token }}'); copied = true; setTimeout(() => copied = false, 1500)" type="button" class="text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300 transition-colors p-1" title="Copy Key">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m-3 8h4m-4 4h4m-4-8h4"></path></svg>
                                        </button>
                                        <span x-show="copied" x-cloak class="absolute left-1/2 -translate-x-1/2 -top-8 px-2 py-1 bg-slate-900 text-white text-[10px] rounded shadow-md z-10 whitespace-nowrap">
                                            Copied!
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-550 dark:text-slate-400">
                                <span class="inline-block bg-slate-100 dark:bg-slate-800 text-[11px] px-2.5 py-1 rounded font-medium">
                                    {{ $token->created_at->format('d-M-y H:i') }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <form action="{{ route('dashboard.developer.tokens.destroy', $token->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to revoke this API key? External systems using this key will immediately fail.');" class="m-0 flex justify-end">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-650 dark:bg-red-950/20 dark:hover:bg-red-900/30 dark:text-red-400 rounded-lg text-xs font-semibold transition-colors border border-red-100 dark:border-red-900/30" title="Revoke Key">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        Revoke
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-10 text-center text-slate-450 dark:text-slate-500">
                                No API Keys generated yet. Click "Generate API key" to create one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- RIGHT PANEL: API Explorer / Docs Panel (Dark IDE Pane) -->
    <div class="w-full lg:w-[45%] xl:w-[42%] bg-slate-950 p-6 md:p-8 flex flex-col space-y-6">
        
        <!-- Header & Language Tabs (Docked) -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-900 pb-5">
            <span class="text-xs font-bold tracking-wider text-slate-400 uppercase">API Reference</span>
            
            <!-- Language Tabs -->
            <div class="flex items-center gap-1 bg-slate-900 p-1 rounded-xl border border-slate-800/80">
                @foreach(['CURL' => 'cURL', 'PHP' => 'PHP', 'NODEJS' => 'Node.js', 'PYTHON' => 'Python', 'JAVA' => 'Java', 'RUBY' => 'Ruby'] as $key => $label)
                    <button @click="activeLang = '{{ $key }}'" 
                            :class="activeLang === '{{ $key }}' ? 'bg-slate-800 text-white shadow-md' : 'text-slate-400 hover:text-slate-200'"
                            class="px-2.5 py-1 text-xs font-semibold rounded-lg transition-all duration-150">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Documentation List (Auto-expanding, no scrollbox limits) -->
        <div class="space-y-6 text-slate-350">
            
            <!-- Category: Timer Operations -->
            <div class="space-y-3">
                <h4 class="text-[10px] font-bold uppercase text-slate-500 tracking-wider">Timer Operations</h4>
                
                <!-- Start Timer -->
                <div x-data="{ open: false }" class="border border-slate-900 rounded-xl overflow-hidden bg-slate-900/10">
                    <button @click="open = !open" class="w-full px-4 py-3.5 flex items-center justify-between hover:bg-slate-900/40 transition-colors text-left">
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-0.5 bg-blue-500/20 text-blue-400 border border-blue-500/30 text-[9px] font-bold rounded">POST</span>
                            <span class="font-mono text-xs font-bold text-white">/api/timer/start</span>
                        </div>
                        <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 text-slate-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    
                    <div x-show="open" x-cloak class="p-5 border-t border-slate-900 space-y-4 bg-slate-950/20">
                        <p class="text-xs text-slate-400 leading-relaxed">Starts a fresh time log for an employee user on a designated task. Fails if a timer is already active.</p>
                        
                        <div>
                            <span class="block text-[10px] font-bold text-slate-500 uppercase mb-2">Request Body (JSON)</span>
                            <div class="overflow-x-auto text-[11px] font-mono text-slate-350 bg-slate-950 p-3 rounded-lg border border-slate-900">
                                {<br>
                                &nbsp;&nbsp;<span class="text-violet-400">"task_id"</span>: <span class="text-amber-300">1</span>, <span class="text-slate-500">// required, integer (must exist in tasks)</span><br>
                                &nbsp;&nbsp;<span class="text-violet-400">"user_id"</span>: <span class="text-amber-300">2</span> <span class="text-slate-500">// required, integer (must exist in users)</span><br>
                                }
                            </div>
                        </div>

                        @include('dashboard.developer.snippets', ['method' => 'POST', 'path' => '/api/timer/start', 'payload' => '{"task_id": 1, "user_id": 2}'])
                    </div>
                </div>

                <!-- Stop Timer -->
                <div x-data="{ open: false }" class="border border-slate-900 rounded-xl overflow-hidden bg-slate-900/10">
                    <button @click="open = !open" class="w-full px-4 py-3.5 flex items-center justify-between hover:bg-slate-900/40 transition-colors text-left">
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-0.5 bg-blue-500/20 text-blue-400 border border-blue-500/30 text-[9px] font-bold rounded">POST</span>
                            <span class="font-mono text-xs font-bold text-white">/api/timer/stop</span>
                        </div>
                        <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 text-slate-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    
                    <div x-show="open" x-cloak class="p-5 border-t border-slate-900 space-y-4 bg-slate-950/20">
                        <p class="text-xs text-slate-400 leading-relaxed">Stops the ongoing timer for a specific employee user and automatically logs the elapsed duration seconds.</p>
                        
                        <div>
                            <span class="block text-[10px] font-bold text-slate-500 uppercase mb-2">Request Body (JSON)</span>
                            <div class="overflow-x-auto text-[11px] font-mono text-slate-350 bg-slate-950 p-3 rounded-lg border border-slate-900">
                                {<br>
                                &nbsp;&nbsp;<span class="text-violet-400">"user_id"</span>: <span class="text-amber-300">2</span> <span class="text-slate-500">// required, integer (must exist in users)</span><br>
                                }
                            </div>
                        </div>

                        @include('dashboard.developer.snippets', ['method' => 'POST', 'path' => '/api/timer/stop', 'payload' => '{"user_id": 2}'])
                    </div>
                </div>
            </div>

            <!-- Category: Manager Analytics -->
            <div class="space-y-3 pt-4 border-t border-slate-900">
                <h4 class="text-[10px] font-bold uppercase text-slate-500 tracking-wider">Manager Analytics</h4>

                <!-- Get Performance -->
                <div x-data="{ open: false }" class="border border-slate-900 rounded-xl overflow-hidden bg-slate-900/10">
                    <button @click="open = !open" class="w-full px-4 py-3.5 flex items-center justify-between hover:bg-slate-900/40 transition-colors text-left">
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-[9px] font-bold rounded">GET</span>
                            <span class="font-mono text-xs font-bold text-white">/api/managers/{id}/performance</span>
                        </div>
                        <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 text-slate-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    
                    <div x-show="open" x-cloak class="p-5 border-t border-slate-900 space-y-4 bg-slate-950/20">
                        <p class="text-xs text-slate-400 leading-relaxed">Computes productivity scores, deadline adherence, and other analytics details for the manager's team.</p>
                        
                        <div>
                            <span class="block text-[10px] font-bold text-slate-500 uppercase mb-2">Query Parameters</span>
                            <div class="text-[11px] font-mono text-slate-350 bg-slate-950 p-3 rounded-lg border border-slate-900 space-y-1">
                                <div><span class="text-sky-400">period</span> <span class="text-slate-500">(optional)</span>: "weekly" | "monthly" | "custom"</div>
                                <div><span class="text-sky-400">start_date</span> <span class="text-slate-500">(optional)</span>: "YYYY-MM-DD" (required if period=custom)</div>
                                <div><span class="text-sky-400">end_date</span> <span class="text-slate-500">(optional)</span>: "YYYY-MM-DD" (required if period=custom)</div>
                            </div>
                        </div>

                        @include('dashboard.developer.snippets', ['method' => 'GET', 'path' => '/api/managers/{id}/performance?period=weekly', 'payload' => null])
                    </div>
                </div>

                <!-- Get Reports List -->
                <div x-data="{ open: false }" class="border border-slate-900 rounded-xl overflow-hidden bg-slate-900/10">
                    <button @click="open = !open" class="w-full px-4 py-3.5 flex items-center justify-between hover:bg-slate-900/40 transition-colors text-left">
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-[9px] font-bold rounded">GET</span>
                            <span class="font-mono text-xs font-bold text-white">/api/managers/{id}/reports</span>
                        </div>
                        <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 text-slate-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    
                    <div x-show="open" x-cloak class="p-5 border-t border-slate-900 space-y-4 bg-slate-950/20">
                        <p class="text-xs text-slate-400 leading-relaxed">Returns historical compiled performance reports generated for this manager.</p>
                        @include('dashboard.developer.snippets', ['method' => 'GET', 'path' => '/api/managers/{id}/reports', 'payload' => null])
                    </div>
                </div>

                <!-- Generate Report -->
                <div x-data="{ open: false }" class="border border-slate-900 rounded-xl overflow-hidden bg-slate-900/10">
                    <button @click="open = !open" class="w-full px-4 py-3.5 flex items-center justify-between hover:bg-slate-900/40 transition-colors text-left">
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-0.5 bg-blue-500/20 text-blue-400 border border-blue-500/30 text-[9px] font-bold rounded">POST</span>
                            <span class="font-mono text-xs font-bold text-white">/api/managers/{id}/generate-report</span>
                        </div>
                        <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 text-slate-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    
                    <div x-show="open" x-cloak class="p-5 border-t border-slate-900 space-y-4 bg-slate-950/20">
                        <p class="text-xs text-slate-400 leading-relaxed">Runs dynamic compilation, schedules an AI scan on manager metrics, and creates a report object.</p>
                        
                        <div>
                            <span class="block text-[10px] font-bold text-slate-500 uppercase mb-2">Request Body (JSON)</span>
                            <div class="overflow-x-auto text-[11px] font-mono text-slate-350 bg-slate-950 p-3 rounded-lg border border-slate-900">
                                {<br>
                                &nbsp;&nbsp;<span class="text-violet-400">"report_type"</span>: <span class="text-amber-300">"weekly"</span>, <span class="text-slate-500">// required, string ("daily"|"weekly"|"monthly")</span><br>
                                &nbsp;&nbsp;<span class="text-violet-400">"start_date"</span>: <span class="text-amber-300">"2026-06-11"</span>, <span class="text-slate-500">// optional</span><br>
                                &nbsp;&nbsp;<span class="text-violet-400">"end_date"</span>: <span class="text-amber-300">"2026-06-18"</span> <span class="text-slate-500">// optional</span><br>
                                }
                            </div>
                        </div>

                        @include('dashboard.developer.snippets', ['method' => 'POST', 'path' => '/api/managers/{id}/generate-report', 'payload' => '{"report_type": "weekly"}'])
                    </div>
                </div>
            </div>

            <!-- Category: Employee Analytics -->
            <div class="space-y-3 pt-4 border-t border-slate-900">
                <h4 class="text-[10px] font-bold uppercase text-slate-500 tracking-wider">Employee Analytics</h4>

                <!-- Get Employee Performance -->
                <div x-data="{ open: false }" class="border border-slate-900 rounded-xl overflow-hidden bg-slate-900/10">
                    <button @click="open = !open" class="w-full px-4 py-3.5 flex items-center justify-between hover:bg-slate-900/40 transition-colors text-left">
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-[9px] font-bold rounded">GET</span>
                            <span class="font-mono text-xs font-bold text-white">/api/employees/{id}/performance</span>
                        </div>
                        <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 text-slate-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    
                    <div x-show="open" x-cloak class="p-5 border-t border-slate-900 space-y-4 bg-slate-950/20">
                        <p class="text-xs text-slate-400 leading-relaxed">Computes individual developer/employee performance metrics, including developer score, task completion rate, and Git activity indicators.</p>
                        
                        <div>
                            <span class="block text-[10px] font-bold text-slate-500 uppercase mb-2">Query Parameters</span>
                            <div class="text-[11px] font-mono text-slate-350 bg-slate-950 p-3 rounded-lg border border-slate-900 space-y-1">
                                <div><span class="text-sky-400">period</span> <span class="text-slate-500">(optional)</span>: "weekly" | "monthly" | "custom"</div>
                                <div><span class="text-sky-400">start_date</span> <span class="text-slate-500">(optional)</span>: "YYYY-MM-DD" (required if period=custom)</div>
                                <div><span class="text-sky-400">end_date</span> <span class="text-slate-500">(optional)</span>: "YYYY-MM-DD" (required if period=custom)</div>
                            </div>
                        </div>

                        @include('dashboard.developer.snippets', ['method' => 'GET', 'path' => '/api/employees/{id}/performance?period=weekly', 'payload' => null])
                    </div>
                </div>

                <!-- Get Employee Reports List -->
                <div x-data="{ open: false }" class="border border-slate-900 rounded-xl overflow-hidden bg-slate-900/10">
                    <button @click="open = !open" class="w-full px-4 py-3.5 flex items-center justify-between hover:bg-slate-900/40 transition-colors text-left">
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-[9px] font-bold rounded">GET</span>
                            <span class="font-mono text-xs font-bold text-white">/api/employees/{id}/reports</span>
                        </div>
                        <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 text-slate-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    
                    <div x-show="open" x-cloak class="p-5 border-t border-slate-900 space-y-4 bg-slate-950/20">
                        <p class="text-xs text-slate-400 leading-relaxed">Returns historical compiled individual performance reports generated for this employee/user.</p>
                        @include('dashboard.developer.snippets', ['method' => 'GET', 'path' => '/api/employees/{id}/reports', 'payload' => null])
                    </div>
                </div>

                <!-- Generate Employee Report -->
                <div x-data="{ open: false }" class="border border-slate-900 rounded-xl overflow-hidden bg-slate-900/10">
                    <button @click="open = !open" class="w-full px-4 py-3.5 flex items-center justify-between hover:bg-slate-900/40 transition-colors text-left">
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-0.5 bg-blue-500/20 text-blue-400 border border-blue-500/30 text-[9px] font-bold rounded">POST</span>
                            <span class="font-mono text-xs font-bold text-white">/api/employees/{id}/generate-report</span>
                        </div>
                        <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 text-slate-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    
                    <div x-show="open" x-cloak class="p-5 border-t border-slate-900 space-y-4 bg-slate-950/20">
                        <p class="text-xs text-slate-400 leading-relaxed">Generates and saves a performance report for an employee, executing AI assessment on their individual activity indicators.</p>
                        
                        <div>
                            <span class="block text-[10px] font-bold text-slate-500 uppercase mb-2">Request Body (JSON)</span>
                            <div class="overflow-x-auto text-[11px] font-mono text-slate-350 bg-slate-950 p-3 rounded-lg border border-slate-900">
                                {<br>
                                &nbsp;&nbsp;<span class="text-violet-400">"report_type"</span>: <span class="text-amber-300">"weekly"</span>, <span class="text-slate-500">// required, string ("daily"|"weekly"|"monthly")</span><br>
                                &nbsp;&nbsp;<span class="text-violet-400">"start_date"</span>: <span class="text-amber-300">"2026-06-11"</span>, <span class="text-slate-500">// optional</span><br>
                                &nbsp;&nbsp;<span class="text-violet-400">"end_date"</span>: <span class="text-amber-300">"2026-06-18"</span> <span class="text-slate-500">// optional</span><br>
                                }
                            </div>
                        </div>

                        @include('dashboard.developer.snippets', ['method' => 'POST', 'path' => '/api/employees/{id}/generate-report', 'payload' => '{"report_type": "weekly"}'])
                    </div>
                </div>
            </div>

            <!-- Category: Predictive Intelligence -->
            <div class="space-y-3 pt-4 border-t border-slate-900">
                <h4 class="text-[10px] font-bold uppercase text-slate-500 tracking-wider">Predictive Intelligence</h4>

                <!-- Get Team Health -->
                <div x-data="{ open: false }" class="border border-slate-900 rounded-xl overflow-hidden bg-slate-900/10">
                    <button @click="open = !open" class="w-full px-4 py-3.5 flex items-center justify-between hover:bg-slate-900/40 transition-colors text-left">
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-[9px] font-bold rounded">GET</span>
                            <span class="font-mono text-xs font-bold text-white">/api/managers/{id}/predictive-health</span>
                        </div>
                        <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 text-slate-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    
                    <div x-show="open" x-cloak class="p-5 border-t border-slate-900 space-y-4 bg-slate-950/20">
                        <p class="text-xs text-slate-400 leading-relaxed">Aggregates burnout risks, overtime spikes, and generates predictive health scoring for the team.</p>
                        @include('dashboard.developer.snippets', ['method' => 'GET', 'path' => '/api/managers/{id}/predictive-health', 'payload' => null])
                    </div>
                </div>

                <!-- Get Predictive Risks -->
                <div x-data="{ open: false }" class="border border-slate-900 rounded-xl overflow-hidden bg-slate-900/10">
                    <button @click="open = !open" class="w-full px-4 py-3.5 flex items-center justify-between hover:bg-slate-900/40 transition-colors text-left">
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-[9px] font-bold rounded">GET</span>
                            <span class="font-mono text-xs font-bold text-white">/api/managers/{id}/predictive-risks</span>
                        </div>
                        <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 text-slate-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    
                    <div x-show="open" x-cloak class="p-5 border-t border-slate-900 space-y-4 bg-slate-950/20">
                        <p class="text-xs text-slate-400 leading-relaxed">Performs detection scans and returns all active risk alerts (e.g. burnout risk, overtime risk) for the team.</p>
                        @include('dashboard.developer.snippets', ['method' => 'GET', 'path' => '/api/managers/{id}/predictive-risks', 'payload' => null])
                    </div>
                </div>
            </div>

            <!-- Category: General -->
            <div class="space-y-3 pt-4 border-t border-slate-900">
                <h4 class="text-[10px] font-bold uppercase text-slate-500 tracking-wider">General</h4>

                <!-- Get Leaderboard -->
                <div x-data="{ open: false }" class="border border-slate-900 rounded-xl overflow-hidden bg-slate-900/10">
                    <button @click="open = !open" class="w-full px-4 py-3.5 flex items-center justify-between hover:bg-slate-900/40 transition-colors text-left">
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-[9px] font-bold rounded">GET</span>
                            <span class="font-mono text-xs font-bold text-white">/api/leaderboard</span>
                        </div>
                        <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 text-slate-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    
                    <div x-show="open" x-cloak class="p-5 border-t border-slate-900 space-y-4 bg-slate-950/20">
                        <p class="text-xs text-slate-400 leading-relaxed">Fetches the global manager analytics rankings leaderboard.</p>
                        
                        <div>
                            <span class="block text-[10px] font-bold text-slate-500 uppercase mb-2">Query Parameters</span>
                            <div class="text-[11px] font-mono text-slate-350 bg-slate-950 p-3 rounded-lg border border-slate-900">
                                <span class="text-sky-400">period</span> <span class="text-slate-500">(optional)</span>: "weekly" | "monthly" (default: "weekly")
                            </div>
                        </div>

                        @include('dashboard.developer.snippets', ['method' => 'GET', 'path' => '/api/leaderboard?period=weekly', 'payload' => null])
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
