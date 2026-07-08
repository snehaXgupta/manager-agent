@extends('layouts.app')

@section('content')
@php
    $messagesJson = json_encode($activeConversation ? $activeConversation->messages->map(function($msg) {
        return [
            'id' => $msg->id,
            'role' => $msg->role,
            'content' => $msg->content,
            'data_sources' => $msg->data_sources ?? [],
            'structured_response' => $msg->structured_response,
            'created_at' => $msg->created_at->toDateTimeString(),
            'isStreaming' => false
        ];
    }) : []);

    $conversationsJson = json_encode($conversations->map(function($conv) {
        return [
            'id' => $conv->id,
            'title' => $conv->title,
            'updated_at' => $conv->updated_at->toDateTimeString()
        ];
    }));

    $suggestedQuestionsJson = json_encode($suggestedQuestions);
@endphp
<!-- Include Chart.js from CDN to guarantee visualization capability -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="flex flex-col lg:flex-row gap-6 h-[calc(100vh-8.5rem)] md:h-[calc(100vh-9.5rem)] overflow-hidden -m-6 md:-m-8"
     x-data="aiAgentChat()"
     x-init="init()">

    <!-- 1. LEFT PANEL: CONVERSATION HISTORY (Desktop Sidebar / Mobile Drawer) -->
    <!-- Desktop History Panel -->
    <div class="hidden lg:flex flex-col w-80 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 shrink-0 h-full">
        <!-- New Chat and Search -->
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 space-y-3">
            <a href="{{ route('dashboard.ai-chat.index') }}" 
               class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-skyAccent hover:bg-sky-600 text-white rounded-xl font-bold transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                New Conversation
            </a>
            
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" 
                       x-model="historySearch"
                       placeholder="Search history..." 
                       class="w-full pl-9 pr-4 py-2 bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-xs text-slate-800 dark:text-slate-200 placeholder:text-slate-400 transition-all">
            </div>
        </div>

        <!-- History List -->
        <div class="flex-1 overflow-y-auto p-2 space-y-1">
            <template x-for="item in filteredConversations()" :key="item.id">
                <div class="group flex items-center justify-between p-2.5 rounded-xl transition-all cursor-pointer"
                     :class="activeConversationId == item.id ? 'bg-sky-50 dark:bg-blue-950/20 text-skyAccent dark:text-blue-400 font-semibold' : 'text-slate-650 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/40'"
                     @click="switchConversation(item.id)">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        <span class="text-xs truncate" x-text="item.title"></span>
                    </div>
                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <!-- Delete conversation -->
                        <button @click.stop="deleteConversation(item.id)" class="p-1 hover:text-red-500 rounded transition-colors" title="Delete Chat">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
            
            <div x-show="filteredConversations().length === 0" class="p-4 text-center text-xs text-slate-400 italic">
                No chat history found.
            </div>
        </div>

        <!-- History Actions Footer -->
        <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
            @if($conversations->isNotEmpty())
                <form action="{{ route('dashboard.ai-chat.clear', ['id' => $activeConversation ? $activeConversation->id : $conversations->first()->id]) }}" method="POST"
                      id="clear-messages-form"
                      @submit.prevent="confirmClearActiveConversation($event)">
                    @csrf
                    <button type="submit" 
                            class="w-full flex items-center justify-center gap-2 px-3 py-2 text-xs font-semibold text-red-500 hover:bg-red-50 dark:hover:bg-red-950/20 border border-red-200 dark:border-red-900/30 rounded-xl transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Clear Current Chat Messages
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Mobile Drawer for Chat History -->
    <div x-cloak 
         x-show="showHistoryDrawer" 
         class="fixed inset-0 z-50 lg:hidden flex" 
         role="dialog" 
         aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/60 transition-opacity" 
             @click="showHistoryDrawer = false"></div>
             
        <!-- Drawer content -->
        <div class="relative flex flex-col w-72 max-w-xs bg-white dark:bg-slate-900 h-full shadow-2xl">
            <div class="p-4 flex items-center justify-between border-b border-slate-200 dark:border-slate-800">
                <span class="font-bold text-sm">Conversations</span>
                <button @click="showHistoryDrawer = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <!-- New Chat & Search inside Drawer -->
            <div class="p-4 space-y-3">
                <a href="{{ route('dashboard.ai-chat.index') }}" 
                   class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-skyAccent hover:bg-sky-600 text-white rounded-xl font-bold text-xs transition-all shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    New Chat
                </a>
                <input type="text" 
                       x-model="historySearch"
                       placeholder="Search history..." 
                       class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:border-skyAccent text-xs text-slate-850 dark:text-slate-200">
            </div>

            <!-- Scrollable list -->
            <div class="flex-1 overflow-y-auto px-2 space-y-1">
                <template x-for="item in filteredConversations()" :key="item.id">
                    <div class="flex items-center justify-between p-2.5 rounded-xl"
                         :class="activeConversationId == item.id ? 'bg-sky-50 dark:bg-blue-950/20 text-skyAccent dark:text-blue-400 font-semibold' : 'text-slate-600 dark:text-slate-450 hover:bg-slate-50'"
                         @click="switchConversation(item.id); showHistoryDrawer = false">
                        <div class="flex items-center gap-2 min-w-0">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                            <span class="text-xs truncate" x-text="item.title"></span>
                        </div>
                        <button @click.stop="deleteConversation(item.id)" class="p-1 text-slate-400 hover:text-red-500">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>


    <!-- 2. CENTER PANEL: THE CHAT WINDOW -->
    <div class="flex-1 flex flex-col h-full bg-slate-50/50 dark:bg-slate-950/20 relative">
        <!-- Chat Area Top Header -->
        <header class="flex items-center justify-between px-6 py-4 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 shadow-sm shrink-0">
            <!-- Mobile Menu and Sidebar Triggers -->
            <div class="flex items-center gap-3">
                <button @click="showHistoryDrawer = true" class="lg:hidden p-1.5 text-slate-500 hover:text-slate-700 dark:hover:text-slate-350 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div class="flex items-center gap-2.5">
                    <div class="relative flex h-2 w-2 shrink-0">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </div>
                    <div>
                        <h2 class="font-bold text-sm text-slate-900 dark:text-white" x-text="activeConversationTitle || 'AI Assistant Chat'"></h2>
                        <span class="block text-[10px] text-slate-400 tracking-wide font-medium uppercase mt-0.5">Workforce Intelligence telemetry</span>
                    </div>
                </div>
            </div>

            <!-- Actions: Filters, Export, Toggle Drawer -->
            <div class="flex items-center gap-2">
                <!-- Date Filters Toggle -->
                <button @click="showFilters = !showFilters" 
                        class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all"
                        :class="startDate || endDate ? 'bg-sky-50 dark:bg-blue-950/30 text-skyAccent dark:text-blue-400 border-sky-200 dark:border-blue-900' : 'bg-white dark:bg-slate-800 text-slate-650 dark:text-slate-300 border-slate-200 dark:border-slate-750 hover:bg-slate-50'">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span>Date Filter</span>
                    <span x-show="startDate || endDate" class="w-1.5 h-1.5 rounded-full bg-skyAccent"></span>
                </button>

                <!-- Export Chat Action -->
                <template x-if="activeConversationId">
                    <a :href="'{{ url('ai-agent/export') }}/' + activeConversationId" 
                       class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-750 text-slate-650 dark:text-slate-300 rounded-lg hover:bg-slate-50 transition-all"
                       title="Export conversation history to JSON file">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        <span class="hidden sm:inline">Export Chat</span>
                    </a>
                </template>

                <!-- Insights Drawer Trigger (Mobile Only) -->
                <button @click="showInsightsDrawer = true" class="lg:hidden p-1.5 text-slate-500 hover:text-slate-700 dark:hover:text-slate-350 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg" title="View sources consulted">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </button>
            </div>
        </header>

        <!-- Date Filters Panel (Collapsible) -->
        <div x-cloak 
             x-show="showFilters" 
             x-transition 
             class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 px-6 py-4 flex flex-wrap items-center gap-4 shadow-inner text-xs z-25 relative">
            <div class="flex items-center gap-2">
                <label class="font-semibold text-slate-500 dark:text-slate-400">Date Range:</label>
                <input type="date" 
                       x-model="startDate" 
                       class="bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-750 rounded-lg px-2.5 py-1.5 focus:outline-none focus:border-skyAccent text-slate-700 dark:text-slate-200">
            </div>
            <div class="flex items-center gap-2">
                <label class="font-semibold text-slate-500 dark:text-slate-400">To:</label>
                <input type="date" 
                       x-model="endDate" 
                       class="bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-750 rounded-lg px-2.5 py-1.5 focus:outline-none focus:border-skyAccent text-slate-700 dark:text-slate-200">
            </div>
            <button @click="clearFilters()" 
                    class="text-xs font-semibold text-slate-450 hover:text-slate-700 dark:hover:text-white transition-colors">
                Reset Range
            </button>
        </div>

        <!-- Chat Bubbles Scroller Area -->
        <div class="flex-1 overflow-y-auto p-6 space-y-6" id="chat-scroll-container">
            <template x-for="(msg, index) in messages" :key="index">
                <div class="flex flex-col" :class="msg.role === 'user' ? 'items-end' : 'items-start'">
                    
                    <!-- Bubble Label Name & Time -->
                    <div class="flex items-center gap-1.5 mb-1 text-[10px] text-slate-405 dark:text-slate-500 font-semibold px-1">
                        <span x-text="msg.role === 'user' ? 'Manager (You)' : 'AI Agent'"></span>
                        <span>&bull;</span>
                        <span x-text="formatTime(msg.created_at)"></span>
                    </div>

                    <!-- Main Chat Bubble -->
                    <div class="max-w-[85%] rounded-2xl p-4 shadow-sm leading-relaxed text-sm transition-all"
                         :class="msg.role === 'user' 
                             ? 'bg-skyAccent text-white rounded-tr-none' 
                             : 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 rounded-tl-none text-slate-800 dark:text-slate-200'">
                        
                        <!-- Text Content -->
                        <div class="whitespace-pre-line text-xs md:text-sm font-medium tracking-normal" x-text="msg.content"></div>

                        <!-- Thinking Indicator Loader -->
                        <template x-if="msg.isStreaming">
                            <div class="flex items-center gap-1 mt-2.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400 dark:bg-slate-550 animate-bounce"></span>
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400 dark:bg-slate-550 animate-bounce [animation-delay:0.2s]"></span>
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400 dark:bg-slate-550 animate-bounce [animation-delay:0.4s]"></span>
                            </div>
                        </template>

                        <!-- Hallucination Protection warning (No Records found) -->
                        <template x-if="!msg.isStreaming && msg.role === 'assistant' && msg.content.includes('Insufficient data available')">
                            <div class="mt-3 p-3 bg-red-50/50 dark:bg-red-950/10 border border-red-200/50 dark:border-red-900/30 rounded-xl flex gap-2 text-xs">
                                <svg class="w-4 h-4 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <span class="text-red-750 dark:text-red-300 font-medium">Telemetry source reported empty or unmapped values. Answers must match database seeds.</span>
                            </div>
                        </template>

                        <!-- AI Structured Visual components & Metrics rendering -->
                        <template x-if="!msg.isStreaming && msg.role === 'assistant' && msg.structured_response">
                            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800 space-y-4">
                                
                                <!-- 1. Inline Visuals Display -->
                                <template x-if="msg.structured_response.visual_type && msg.structured_response.visual_type !== 'null'">
                                    <div class="bg-slate-50 dark:bg-slate-950 rounded-xl p-4 border border-slate-200 dark:border-slate-800 shadow-inner">
                                        
                                        <!-- A. KPI GRID -->
                                        <template x-if="msg.structured_response.visual_type === 'kpi'">
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <template x-for="row in msg.structured_response.visual_data.rows">
                                                    <div class="bg-white dark:bg-slate-900 px-4 py-3 rounded-xl border border-slate-150 dark:border-slate-800 shadow-sm text-center">
                                                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider" x-text="row[0]"></span>
                                                        <span class="block text-xl font-extrabold text-skyAccent dark:text-blue-400 mt-0.5" x-text="row[1]"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>

                                        <!-- B. TABLES & LEADERBOARDS -->
                                        <template x-if="msg.structured_response.visual_type === 'table' || msg.structured_response.visual_type === 'leaderboard'">
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-left text-xs">
                                                    <thead>
                                                        <tr class="border-b border-slate-200 dark:border-slate-850">
                                                            <template x-for="header in msg.structured_response.visual_data.headers">
                                                                <th class="pb-2 font-bold text-slate-400 uppercase tracking-wider" x-text="header"></th>
                                                            </template>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-850/80">
                                                        <template x-for="row in msg.structured_response.visual_data.rows">
                                                            <tr class="hover:bg-slate-100/40 dark:hover:bg-slate-900/30">
                                                                <template x-for="cell in row">
                                                                    <td class="py-2.5 font-semibold text-slate-700 dark:text-slate-300" x-text="cell"></td>
                                                                </template>
                                                            </tr>
                                                        </template>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </template>

                                        <!-- C. CHART CANVAS -->
                                        <template x-if="msg.structured_response.visual_type === 'chart'">
                                            <div class="relative h-48 w-full">
                                                <canvas :id="'chart-' + msg.id"></canvas>
                                            </div>
                                        </template>

                                    </div>
                                </template>

                                <!-- 2. Supporting Metrics pills -->
                                <template x-if="msg.structured_response.supporting_metrics && msg.structured_response.supporting_metrics.length > 0">
                                    <div class="space-y-1.5">
                                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Metrics Matrix</span>
                                        <div class="flex flex-wrap gap-1.5">
                                            <template x-for="metric in msg.structured_response.supporting_metrics">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-bold bg-sky-50 dark:bg-blue-950/20 text-skyAccent dark:text-blue-450 border border-sky-100 dark:border-blue-900/30" x-text="metric"></span>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <!-- 3. AI Analysis logic -->
                                <template x-if="msg.structured_response.ai_analysis">
                                    <div class="p-3 bg-amber-50/40 dark:bg-amber-950/10 border border-amber-200/50 dark:border-amber-900/30 rounded-xl flex gap-3 text-xs">
                                        <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364.364l-.707.707M21 12h-1M4 9H3m15.364 6.364l-.707-.707M6.343 6.343l.707-.707m9.9 5.05a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        <div>
                                            <span class="font-bold text-amber-800 dark:text-amber-300 block mb-0.5">Telemetry Insights</span>
                                            <p class="text-slate-650 dark:text-slate-350 leading-relaxed font-semibold" x-text="msg.structured_response.ai_analysis"></p>
                                        </div>
                                    </div>
                                </template>

                                <!-- 4. Actionable Recommendations -->
                                <template x-if="msg.structured_response.recommendations && msg.structured_response.recommendations.length > 0">
                                    <div class="space-y-1.5">
                                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">AI Recommendations</span>
                                        <ul class="space-y-1 text-xs font-semibold">
                                            <template x-for="rec in msg.structured_response.recommendations">
                                                <li class="flex items-start gap-2">
                                                    <svg class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <span class="text-slate-650 dark:text-slate-350 leading-relaxed" x-text="rec"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </template>
                            </div>
                        </template>

                    </div>
                </div>
            </template>

            <!-- Loading placeholder bubble -->
            <div x-show="loading" class="flex flex-col items-start animate-pulse">
                <div class="flex items-center gap-1.5 mb-1 text-[10px] text-slate-400 font-semibold px-1">
                    <span>AI Assistant</span>
                    <span>&bull;</span>
                    <span>Typing...</span>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl rounded-tl-none p-4 w-40">
                    <div class="flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-450 dark:bg-slate-600 animate-bounce"></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-450 dark:bg-slate-600 animate-bounce [animation-delay:0.2s]"></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-450 dark:bg-slate-600 animate-bounce [animation-delay:0.4s]"></span>
                    </div>
                </div>
            </div>

            <!-- Empty Conversation Welcome Screen -->
            <div x-show="messages.length === 0" class="flex flex-col items-center justify-center text-center h-full max-w-xl mx-auto space-y-6 my-10">
                <div class="w-16 h-16 rounded-3xl bg-indigo-50 dark:bg-indigo-950/30 text-skyAccent dark:text-blue-400 flex items-center justify-center text-4xl shadow-inner font-bold">
                    🚀
                </div>
                <div>
                    <h3 class="font-extrabold text-lg text-slate-900 dark:text-white">Manager Agent Intelligence Hub</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mt-2">
                        Query employees performance metrics, workload distribution, gitlab metrics, burnout risk levels, attendance trend summaries, and projects completion forecasts. Scoped strictly by Role Based Access limits.
                    </p>
                </div>
                
                <!-- Quick suggested questions grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 w-full">
                    <template x-for="q in suggestedQuestions" :key="q">
                        <button @click="selectSuggested(q)" 
                                class="p-3 text-xs font-semibold border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-skyAccent dark:hover:border-blue-500 hover:bg-sky-50/20 dark:hover:bg-blue-950/20 rounded-xl text-left text-slate-750 dark:text-slate-350 transition-all flex justify-between items-center gap-2">
                            <span class="truncate" x-text="q"></span>
                            <span class="text-skyAccent font-bold">&rarr;</span>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Chat Input Form & Suggestions Bottom section -->
        <footer class="p-4 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shrink-0">
            <!-- Inline Mini-suggested pills when already inside chat -->
            <div x-show="messages.length > 0" class="flex items-center gap-2 overflow-x-auto pb-3 -mx-4 px-4 whitespace-nowrap scrollbar-none" style="-webkit-overflow-scrolling: touch;">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider self-center">Follow-ups:</span>
                <template x-for="q in suggestedQuestions.slice(0, 3)" :key="q">
                    <button @click="selectSuggested(q)" 
                            class="px-2.5 py-1 text-[11px] font-bold border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-650 dark:text-slate-400 hover:border-skyAccent hover:text-skyAccent dark:hover:text-blue-400 rounded-lg transition-colors">
                        <span x-text="q"></span>
                    </button>
                </template>
            </div>

            <!-- Main Input Area Form -->
            <form @submit.prevent="submitMessage()" class="relative flex items-center bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-800 rounded-2xl focus-within:border-skyAccent dark:focus-within:border-blue-500 transition-all shadow-inner">
                <input type="text"
                       x-model="question"
                       placeholder="Ask about top performers, burnout risks, gitlab activities..."
                       class="flex-1 bg-transparent px-5 py-4 text-xs md:text-sm focus:outline-none text-slate-800 dark:text-slate-250 placeholder:text-slate-400"
                       :disabled="loading">
                
                <!-- Send button -->
                <button type="submit" 
                        class="mr-2 p-2.5 bg-skyAccent hover:bg-sky-600 text-white rounded-xl transition-all shadow-md shrink-0 flex items-center justify-center"
                        :class="loading || !question.trim() ? 'opacity-50 cursor-not-allowed' : ''"
                        :disabled="loading || !question.trim()">
                    <svg class="w-4 h-4 transform rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                </button>
            </form>
            <div class="mt-2 text-center text-[10px] text-slate-400">
                Hallucination Protected. Insufficient parameters default to "Insufficient data available".
            </div>
        </footer>
    </div>


    <!-- 3. RIGHT PANEL: INSIGHTS & SOURCES USED -->
    <!-- Desktop Panel -->
    <div class="hidden lg:flex flex-col w-80 bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 shrink-0 h-full p-6 overflow-y-auto">
        <h3 class="font-bold text-xs text-slate-400 uppercase tracking-wider mb-4">Context telemetry</h3>
        
        <div class="space-y-6">
            <!-- Data Sources Used -->
            <div class="space-y-3">
                <span class="block text-xs font-bold text-slate-800 dark:text-slate-200">Data Sources Consulted</span>
                <div class="flex flex-col gap-2">
                    <template x-if="lastAssistantMessage()">
                        <template x-for="src in lastAssistantMessage().data_sources">
                            <div class="flex items-center gap-2 text-xs font-semibold px-3 py-2 bg-indigo-50/50 dark:bg-indigo-950/20 text-skyAccent dark:text-blue-400 rounded-xl border border-indigo-100/50 dark:border-blue-900/30">
                                <span class="w-1.5 h-1.5 rounded-full bg-skyAccent"></span>
                                <span x-text="src"></span>
                            </div>
                        </template>
                    </template>
                    <template x-if="!lastAssistantMessage()">
                        <div class="space-y-2 text-xs font-semibold text-slate-500">
                            <!-- Show connected resources default checklist -->
                            <div class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> employees db</div>
                            <div class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> teams & projects</div>
                            <div class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> tasks telemetry</div>
                            <div class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> attendance logs</div>
                            <div class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> gitlab activity logs</div>
                            <div class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> predictive risk engine</div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Active Conversation summary stats -->
            <div class="space-y-3 border-t border-slate-100 dark:border-slate-800 pt-4">
                <span class="block text-xs font-bold text-slate-800 dark:text-slate-200">AI Memory Engine</span>
                <div class="p-3 bg-slate-50 dark:bg-slate-950 rounded-xl border border-slate-200 dark:border-slate-800 space-y-2 text-xs font-semibold">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Context Window</span>
                        <span class="text-slate-700 dark:text-slate-350">Active</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Chat Length</span>
                        <span class="text-slate-700 dark:text-slate-350" x-text="messages.length + ' Turns'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Active Role Scope</span>
                        <span class="text-skyAccent font-extrabold uppercase text-[10px] tracking-wide" x-text="'{{ session('active_role', auth()->user()->role) }}'"></span>
                    </div>
                </div>
            </div>

            <!-- Executive Quick summary (Promotions/Risks) -->
            <template x-if="lastAssistantMessage() && lastAssistantMessage().structured_response">
                <div class="space-y-3 border-t border-slate-100 dark:border-slate-800 pt-4">
                    <span class="block text-xs font-bold text-slate-850 dark:text-slate-200">Session Quick Analysis</span>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed font-semibold italic" x-text="lastAssistantMessage().structured_response.ai_analysis"></p>
                </div>
            </template>
        </div>
    </div>

    <!-- Mobile Drawer for Insights & Sources -->
    <div x-cloak 
         x-show="showInsightsDrawer" 
         class="fixed inset-0 z-50 lg:hidden flex" 
         role="dialog" 
         aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/60 transition-opacity" 
             @click="showInsightsDrawer = false"></div>
             
        <!-- Drawer content -->
        <div class="relative flex flex-col w-72 max-w-xs bg-white dark:bg-slate-900 h-full shadow-2xl ml-auto p-5 overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3 mb-4">
                <span class="font-bold text-sm">Telemetry Insights</span>
                <button @click="showInsightsDrawer = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div class="space-y-6">
                <!-- Sources -->
                <div class="space-y-3">
                    <span class="block text-xs font-bold text-slate-800 dark:text-slate-200">Sources Scanned</span>
                    <div class="flex flex-col gap-2">
                        <template x-if="lastAssistantMessage()">
                            <template x-for="src in lastAssistantMessage().data_sources">
                                <div class="flex items-center gap-2 text-xs px-3 py-2 bg-indigo-50/50 dark:bg-indigo-950/20 text-skyAccent dark:text-blue-400 rounded-xl border border-indigo-150/40">
                                    <span class="w-1.5 h-1.5 rounded-full bg-skyAccent"></span>
                                    <span x-text="src"></span>
                                </div>
                            </template>
                        </template>
                        <template x-if="!lastAssistantMessage()">
                            <span class="text-xs text-slate-400 italic">No telemetry data scanned yet.</span>
                        </template>
                    </div>
                </div>
                
                <!-- Engine -->
                <div class="space-y-3 border-t border-slate-100 dark:border-slate-850 pt-4">
                    <span class="block text-xs font-bold text-slate-800">Engine State</span>
                    <div class="p-3 bg-slate-50 dark:bg-slate-950 rounded-xl text-xs space-y-2">
                        <div class="flex justify-between">
                            <span class="text-slate-450">Active Role Scope</span>
                            <span class="text-skyAccent uppercase font-bold text-[10px]" x-text="'{{ session('active_role', auth()->user()->role) }}'"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Alpine Script Definition -->
<script>
function aiAgentChat() {
    return {
        activeConversationId: {{ $activeConversation ? $activeConversation->id : 'null' }},
        activeConversationTitle: '{{ $activeConversation ? $activeConversation->title : '' }}',
        historySearch: '',
        startDate: '{{ request('start_date') }}',
        endDate: '{{ request('end_date') }}',
        question: '',
        messages: {!! $messagesJson !!},
        conversations: {!! $conversationsJson !!},
        suggestedQuestions: {!! $suggestedQuestionsJson !!},
        loading: false,
        showFilters: false,
        showHistoryDrawer: false,
        showInsightsDrawer: false,

        init() {
            this.scrollToBottom();
            this.$nextTick(() => {
                this.renderCharts();
            });
        },

        filteredConversations() {
            if (!this.historySearch.trim()) return this.conversations;
            const search = this.historySearch.toLowerCase();
            return this.conversations.filter(c => c.title.toLowerCase().includes(search));
        },

        clearFilters() {
            this.startDate = '';
            this.endDate = '';
        },

        formatTime(dateStr) {
            if (!dateStr) return '';
            try {
                const date = new Date(dateStr);
                return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            } catch(e) {
                return '';
            }
        },

        switchConversation(id) {
            window.location.href = `?conversation_id=${id}`;
        },

        confirmClearActiveConversation(e) {
            if (confirm('Are you sure you want to clear all messages in this conversation history?')) {
                e.target.submit();
            }
        },

        deleteConversation(id) {
            if (confirm('Are you sure you want to delete this entire conversation?')) {
                // Submit delete action via dummy form dynamically
                const form = document.createElement('form');
                form.action = `{{ url('ai-agent/delete') }}/${id}`;
                form.method = 'POST';
                
                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                const method = document.createElement('input');
                method.type = 'hidden';
                method.name = '_method';
                method.value = 'DELETE';
                
                form.appendChild(csrf);
                form.appendChild(method);
                document.body.appendChild(form);
                form.submit();
            }
        },

        selectSuggested(q) {
            this.question = q;
            this.submitMessage();
        },

        lastAssistantMessage() {
            for (let i = this.messages.length - 1; i >= 0; i--) {
                if (this.messages[i].role === 'assistant' && !this.messages[i].isStreaming) {
                    return this.messages[i];
                }
            }
            return null;
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const chatBody = document.getElementById('chat-scroll-container');
                if (chatBody) {
                    chatBody.scrollTop = chatBody.scrollHeight;
                }
            });
        },

        async submitMessage() {
            if (!this.question.trim() || this.loading) return;

            const userMsg = this.question;
            this.question = '';
            
            // Push user message to UI immediately
            this.messages.push({
                role: 'user',
                content: userMsg,
                created_at: new Date().toISOString(),
                isStreaming: false
            });

            this.loading = true;
            this.scrollToBottom();

            // Create a temporary stream index inside messages list for the typing assistant
            const streamIndex = this.messages.push({
                id: 'temp-' + Date.now(),
                role: 'assistant',
                content: '',
                isStreaming: true,
                data_sources: [],
                structured_response: null,
                created_at: new Date().toISOString()
            }) - 1;

            try {
                const response = await fetch('{{ route('dashboard.ai-chat.send') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'text/event-stream',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Request-Stream': 'true'
                    },
                    body: JSON.stringify({
                        question: userMsg,
                        conversation_id: this.activeConversationId,
                        start_date: this.startDate,
                        end_date: this.endDate,
                        stream: true
                    })
                });

                if (!response.ok) {
                    throw new Error('Server responded with an error');
                }

                // Resolve conversation ID and title from headers immediately
                const isNewConversation = !this.activeConversationId;
                const newConvId = response.headers.get('X-Conversation-Id');
                const newConvTitle = decodeURIComponent(response.headers.get('X-Conversation-Title') || '');

                if (newConvId) {
                    this.activeConversationId = parseInt(newConvId);
                    this.activeConversationTitle = newConvTitle;
                }

                this.loading = false;

                // Read streaming body chunks
                const reader = response.body.getReader();
                const decoder = new TextDecoder('utf-8');
                let accumulated = '';

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;

                    const chunk = decoder.decode(value, { stream: true });
                    accumulated += chunk;

                    // Extract actual content (separated from structured JSON data at the end)
                    const marker = "\n\n[STRUCTURED_METRICS_DATA_JSON]\n";
                    if (accumulated.includes(marker)) {
                        const parts = accumulated.split(marker);
                        this.messages[streamIndex].content = parts[0];
                    } else {
                        this.messages[streamIndex].content = accumulated;
                    }
                    this.scrollToBottom();
                }

                // Stream ended, parse the final structured JSON if present
                const marker = "\n\n[STRUCTURED_METRICS_DATA_JSON]\n";
                if (accumulated.includes(marker)) {
                    const parts = accumulated.split(marker);
                    const directText = parts[0];
                    const jsonString = parts[1];

                    try {
                        const reply = JSON.parse(jsonString);
                        this.messages[streamIndex].content = reply.direct_answer || directText;
                        this.messages[streamIndex].data_sources = reply.data_sources_used || [];
                        this.messages[streamIndex].structured_response = reply;
                        this.messages[streamIndex].isStreaming = false;

                        // Trigger Chart.js render if visual exists
                        if (reply && reply.visual_type === 'chart') {
                            this.renderCharts();
                        }
                    } catch(e) {
                        console.error('Failed to parse final metrics JSON', e);
                        this.messages[streamIndex].isStreaming = false;
                    }
                } else {
                    this.messages[streamIndex].isStreaming = false;
                }

                this.scrollToBottom();

                if (isNewConversation && this.activeConversationId) {
                    // For a new conversation, redirect to set page state
                    window.location.href = `?conversation_id=${this.activeConversationId}`;
                } else if (this.activeConversationId) {
                    // For existing conversation, update URL history state
                    window.history.pushState(null, '', `?conversation_id=${this.activeConversationId}`);
                }

            } catch (error) {
                console.error(error);
                this.loading = false;
                this.messages[streamIndex].content = 'Failed to connect to the AI Manager Agent service. Make sure NVIDIA API key or Fallback Engine is running.';
                this.messages[streamIndex].isStreaming = false;
                this.scrollToBottom();
            }
        },

        renderCharts() {
            this.messages.forEach(msg => {
                if (msg.structured_response && msg.structured_response.visual_type === 'chart') {
                    const chartData = msg.structured_response.visual_data;
                    if (chartData && chartData.chart_type && chartData.chart_labels) {
                        const canvasId = 'chart-' + msg.id;
                        this.$nextTick(() => {
                            const canvas = document.getElementById(canvasId);
                            if (canvas) {
                                // Destroy old instance to avoid hover render glitches
                                if (canvas.$chart) {
                                    canvas.$chart.destroy();
                                }
                                const ctx = canvas.getContext('2d');
                                const isDark = document.documentElement.classList.contains('dark');
                                const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.05)';
                                const textColor = isDark ? '#94a3b8' : '#64748b';
                                
                                let datasets = [];
                                if (chartData.chart_type === 'pie') {
                                    datasets = [{
                                        data: chartData.chart_values,
                                        backgroundColor: ['#0ea5e9', '#f43f5e', '#10b981', '#f59e0b', '#8b5cf6'],
                                        borderWidth: isDark ? 2 : 1,
                                        borderColor: isDark ? '#0f172a' : '#ffffff'
                                    }];
                                } else {
                                    datasets = [{
                                        label: 'Count / Score',
                                        data: chartData.chart_values,
                                        backgroundColor: '#0ea5e9',
                                        borderRadius: 6,
                                        borderWidth: 0
                                    }];
                                }

                                canvas.$chart = new Chart(ctx, {
                                    type: chartData.chart_type,
                                    data: {
                                        labels: chartData.chart_labels,
                                        datasets: datasets
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: {
                                            legend: {
                                                display: chartData.chart_type === 'pie',
                                                position: 'bottom',
                                                labels: {
                                                    color: textColor,
                                                    boxWidth: 10,
                                                    font: { size: 9 }
                                                }
                                            }
                                        },
                                        scales: chartData.chart_type === 'pie' ? {} : {
                                            y: {
                                                beginAtZero: true,
                                                grid: { color: gridColor },
                                                ticks: { color: textColor, font: { size: 9 } }
                                            },
                                            x: {
                                                grid: { display: false },
                                                ticks: { color: textColor, font: { size: 9 } }
                                            }
                                        }
                                    }
                                });
                            }
                        });
                    }
                }
            });
        }
    };
}
</script>

<style>
/* Custom mini styling to hide standard scrollbar in follow-up recommendations row */
.scrollbar-none::-webkit-scrollbar {
    display: none;
}
.scrollbar-none {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
@endsection
