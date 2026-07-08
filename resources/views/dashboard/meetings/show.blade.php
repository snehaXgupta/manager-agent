@extends('layouts.app')

@section('content')
<div class="space-y-8 animate-fade-in" 
     x-data="meetingDetails({
        id: {{ $meeting->id }},
        initialStatus: '{{ $meeting->status }}',
        initialActionItems: {{ json_encode($meeting->actionItems->map(function($item) {
            return [
                'id' => $item->id,
                'action_item' => $item->action_item,
                'assigned_to' => $item->assigned_to,
                'assignee_name' => $item->assignee->name ?? null,
                'due_date' => $item->due_date ? $item->due_date->format('Y-m-d') : null,
                'priority' => $item->priority,
                'status' => $item->status
            ];
        })) }},
        initialDecisions: {{ json_encode($meeting->decisions) }},
        initialTranscript: {{ json_encode($meeting->transcript) }},
        teamMembers: {{ json_encode($meeting->team ? $meeting->team->members->map(function($m) {
            return ['id' => $m->id, 'name' => $m->name];
        }) : []) }}
     })">

    <!-- Header Navigation -->
    <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
        <a href="{{ $meeting->team_id ? route('dashboard.teams.show', $meeting->team_id) . '?tab=meetings' : route('dashboard.fireflies-test') }}" 
           class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-skyAccent dark:text-slate-400 dark:hover:text-blue-400 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to Team Meetings
        </a>
        <div class="flex items-center gap-2">
            <span class="text-xs font-semibold text-slate-400 uppercase">Team:</span>
            <span class="text-xs font-bold text-slate-850 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 px-2.5 py-1 rounded-md">{{ $meeting->team->name ?? 'No Team' }}</span>
        </div>
    </div>

    @if(!empty($error))
        <!-- Validation Error Message Display -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-8 shadow-sm text-center space-y-4 max-w-xl mx-auto my-12">
            <div class="w-16 h-16 rounded-full bg-rose-50 dark:bg-rose-950/20 text-rose-500 flex items-center justify-center mx-auto text-2xl">
                ⚠️
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Verification Failure</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ $error }}
            </p>
            <div class="pt-4 flex justify-center gap-4">
                <a href="{{ $meeting->team_id ? route('dashboard.teams.show', $meeting->team_id) . '?tab=meetings' : route('dashboard.fireflies-test') }}" 
                   class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-750 dark:text-slate-300 rounded-xl text-xs font-bold shadow-sm transition-all">
                    Go Back
                </a>
                <a href="{{ route('dashboard.fireflies-test') }}" 
                   class="px-4 py-2 bg-skyAccent hover:bg-sky-650 text-white rounded-xl text-xs font-bold shadow transition-all">
                    Fireflies Integration Test
                </a>
            </div>
        </div>
    @else
        <!-- Main Grid Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left Column: Meeting Details & Info -->
            <div class="space-y-6 lg:col-span-1">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-6">
                    <!-- Title & Status Badge -->
                    <div class="space-y-2">
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white leading-snug">{{ $meeting->title }}</h3>
                        <div class="flex items-center gap-2 pt-1">
                            <!-- Dynamic Status Badge -->
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold border transition-colors duration-300"
                                  :class="{
                                    'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/20 dark:text-blue-400 dark:border-blue-800': status === 'Scheduled',
                                    'bg-green-50 text-green-700 border-green-200 dark:bg-green-950/20 dark:text-green-400 dark:border-green-800': status === 'Completed',
                                    'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/20 dark:text-red-400 dark:border-red-800': status === 'Cancelled'
                                  }"
                                  x-text="status">
                            </span>
                        </div>
                    </div>

                    <!-- Description -->
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed font-medium">
                        {{ $meeting->description ?: 'No meeting agenda or description provided.' }}
                    </p>

                    <!-- Meeting Meta Grid -->
                    <div class="border-t border-slate-100 dark:border-slate-800/80 pt-4 space-y-3.5 text-xs">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400 font-medium">Scheduled Date</span>
                            <strong class="text-slate-800 dark:text-slate-200 font-semibold" x-text="formatDate('{{ $meeting->meeting_date }}')"></strong>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400 font-medium">Start Time</span>
                            <strong class="text-slate-800 dark:text-slate-200 font-semibold" x-text="formatTime('{{ $meeting->meeting_time }}')"></strong>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400 font-medium">Duration</span>
                            <strong class="text-slate-800 dark:text-slate-200 font-semibold">{{ $meeting->duration }} minutes</strong>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400 font-medium">Organized By</span>
                            <strong class="text-slate-800 dark:text-slate-200 font-semibold">{{ $meeting->creator->name ?? 'System' }}</strong>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400 font-medium">Fireflies ID</span>
                            <strong class="text-slate-800 dark:text-slate-200 font-mono text-[10px] truncate max-w-[130px]" title="{{ $meeting->fireflies_meeting_id }}">
                                {{ $meeting->fireflies_meeting_id ?? 'Not Synced' }}
                            </strong>
                        </div>
                    </div>

                    <!-- Actions / Controls -->
                    <div class="border-t border-slate-100 dark:border-slate-800/80 pt-5 flex flex-col gap-2.5">
                        @if($meeting->meeting_link)
                            <a href="{{ $meeting->meeting_link }}" target="_blank" 
                               class="w-full text-center px-4 py-2.5 rounded-xl text-xs font-bold bg-emerald-500 hover:bg-emerald-600 dark:bg-green-600 dark:hover:bg-green-700 text-white shadow-sm hover:shadow transition-all flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                Join Meeting URL
                            </a>
                        @endif

                        <div class="grid grid-cols-2 gap-2" x-show="status === 'Scheduled'">
                            <button @click="completeMeeting()" 
                                    class="px-3 py-2 rounded-xl text-xs font-bold bg-skyAccent hover:bg-sky-600 text-white shadow-sm transition-all text-center">
                                Mark Complete
                            </button>
                            <button @click="cancelMeeting()" 
                                    class="px-3 py-2 rounded-xl text-xs font-bold bg-rose-50 border border-rose-200 hover:bg-rose-100 text-rose-700 dark:bg-red-950/20 dark:border-red-900/30 dark:text-red-400 dark:hover:bg-red-900/40 transition-all text-center">
                                Cancel Meeting
                            </button>
                        </div>

                    </div>
                </div>

                <!-- Sync Attendees Card -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-4">
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-2 mb-2 flex items-center justify-between">
                        <span>Meeting Attendees</span>
                        <span class="px-2 py-0.5 rounded bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-bold">{{ $meeting->meetingParticipants->count() }}</span>
                    </h4>
                    <div class="space-y-3.5 max-h-64 overflow-y-auto pr-1 font-sans">
                        @forelse($meeting->meetingParticipants as $participant)
                            <div class="flex items-center justify-between p-2.5 bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-850 rounded-2xl">
                                <div class="flex items-center gap-3">
                                    <div class="w-7 h-7 rounded-xl bg-sky-50 dark:bg-blue-950/20 text-skyAccent dark:text-blue-400 flex items-center justify-center font-bold text-xs">
                                        {{ substr($participant->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <span class="block text-xs font-bold text-slate-800 dark:text-slate-200">{{ $participant->name }}</span>
                                        <span class="block text-[9px] text-slate-450 truncate max-w-[150px]" title="{{ $participant->email }}">{{ $participant->email }}</span>
                                    </div>
                                </div>
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500" title="Fireflies Sync Participant"></span>
                            </div>
                        @empty
                            <div class="text-center py-4 text-xs text-slate-400 italic">No synced participants found.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Right Column: Interactive Notebook & Transcripts -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-sm overflow-hidden flex flex-col min-h-[500px]">
                    
                    <!-- Tab Headings -->
                    <div class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20 px-6 pt-3 flex gap-6">
                        <button @click="activeTab = 'notes'" 
                                :class="activeTab === 'notes' ? 'border-skyAccent text-skyAccent dark:border-blue-400 dark:text-blue-400 font-bold' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 font-medium'" 
                                class="pb-3 border-b-2 text-xs transition-all flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                            AI Summary & Sentiment
                        </button>
                        <button @click="activeTab = 'action_items'" 
                                :class="activeTab === 'action_items' ? 'border-skyAccent text-skyAccent dark:border-blue-400 dark:text-blue-400 font-bold' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 font-medium'" 
                                class="pb-3 border-b-2 text-xs transition-all flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            Action Items Tracker (<span x-text="actionItems.length"></span>)
                        </button>
                        <button @click="activeTab = 'decisions'" 
                                :class="activeTab === 'decisions' ? 'border-skyAccent text-skyAccent dark:border-blue-400 dark:text-blue-400 font-bold' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 font-medium'" 
                                class="pb-3 border-b-2 text-xs transition-all flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                            Decisions Log (<span x-text="decisions.length"></span>)
                        </button>
                        <button @click="activeTab = 'transcript'" 
                                :class="activeTab === 'transcript' ? 'border-skyAccent text-skyAccent dark:border-blue-400 dark:text-blue-400 font-bold' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 font-medium'" 
                                class="pb-3 border-b-2 text-xs transition-all flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                            Full Transcript
                        </button>
                    </div>

                    <!-- Tab Contents -->
                    <div class="p-6 flex-1 flex flex-col justify-between font-sans">
                        
                        <!-- TAB 1: AI SUMMARY & SENTIMENT -->
                        <div x-show="activeTab === 'notes'" class="space-y-6 flex-1">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Meeting Notes Summary</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-slate-400">Sentiment:</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold border"
                                          :class="{
                                            'bg-green-50 text-green-700 border-green-200 dark:bg-green-950/20 dark:text-green-400 dark:border-green-800': transcript && transcript.sentiment === 'Positive',
                                            'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-805/20 dark:text-slate-400 dark:border-slate-800': !transcript || transcript.sentiment !== 'Positive'
                                          }"
                                          x-text="transcript ? transcript.sentiment : 'Pending Completion'">
                                    </span>
                                </div>
                            </div>

                            <!-- Notes Body -->
                            <div class="prose dark:prose-invert max-w-none text-slate-655 dark:text-slate-300 text-sm leading-relaxed whitespace-pre-line font-medium"
                                 x-text="transcript ? transcript.summary : 'Summary notes will load directly from the Fireflies API.'">
                            </div>
                        </div>

                        <!-- TAB 2: ACTION ITEMS TRACKER -->
                        <div x-show="activeTab === 'action_items'" class="space-y-6 flex-1">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                    Extracted Action Items
                                </span>
                                <button @click="showAddActionModal = true" 
                                        class="px-2.5 py-1.5 bg-skyAccent hover:bg-sky-650 text-white rounded-lg text-xs font-bold shadow transition-all">
                                    + Create Action Item
                                </button>
                            </div>

                            <!-- List / Table -->
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="text-slate-400 text-[10px] font-bold uppercase tracking-wider border-b border-slate-150 dark:border-slate-800 pb-3">
                                            <th class="py-2.5">Action item description</th>
                                            <th class="py-2.5">Assignee</th>
                                            <th class="py-2.5">Due date</th>
                                            <th class="py-2.5">Priority</th>
                                            <th class="py-2.5">Status</th>
                                            <th class="py-2.5 text-right">Delete</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs">
                                        <template x-for="item in actionItems" :key="item.id">
                                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                                                <td class="py-3 font-medium text-slate-800 dark:text-slate-200 max-w-xs pr-4 truncate" :title="item.action_item" x-text="item.action_item"></td>
                                                <td class="py-3">
                                                    <select :value="item.assigned_to" @change="updateActionAssignee(item.id, $event.target.value)"
                                                            class="px-2 py-1.5 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs rounded-xl outline-none text-slate-700 dark:text-slate-300">
                                                        <option value="">Unassigned</option>
                                                        <template x-for="member in teamMembers" :key="member.id">
                                                            <option :value="member.id" x-text="member.name" :selected="member.id == item.assigned_to"></option>
                                                        </template>
                                                    </select>
                                                </td>
                                                <td class="py-3">
                                                    <input type="date" :value="item.due_date" @change="updateActionDueDate(item.id, $event.target.value)"
                                                           class="px-2 py-1 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs rounded-xl outline-none text-slate-700 dark:text-slate-300">
                                                </td>
                                                <td class="py-3">
                                                    <select :value="item.priority" @change="updateActionPriority(item.id, $event.target.value)"
                                                            class="px-2 py-1 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs rounded-xl outline-none text-slate-700 dark:text-slate-350"
                                                            :class="{
                                                                'text-red-650 font-bold bg-red-50 dark:bg-red-950/20': item.priority === 'High',
                                                                'text-amber-650 font-bold bg-amber-50 dark:bg-amber-950/20': item.priority === 'Medium',
                                                                'text-green-650 font-bold bg-green-50 dark:bg-green-950/20': item.priority === 'Low'
                                                            }">
                                                        <option value="High">High</option>
                                                        <option value="Medium">Medium</option>
                                                        <option value="Low">Low</option>
                                                    </select>
                                                </td>
                                                <td class="py-3">
                                                    <select :value="item.status" @change="updateActionStatus(item.id, $event.target.value)"
                                                            class="px-2 py-1.5 border border-slate-205 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs rounded-xl font-semibold outline-none"
                                                            :class="{
                                                                'text-green-650 bg-green-50 dark:bg-green-950/20': item.status === 'Completed',
                                                                'text-skyAccent bg-sky-50 dark:bg-blue-950/20': item.status === 'In Progress',
                                                                'text-slate-500 bg-slate-50 dark:bg-slate-800/40': item.status === 'Pending'
                                                            }">
                                                        <option value="Pending">Pending</option>
                                                        <option value="In Progress">In Progress</option>
                                                        <option value="Completed">Completed</option>
                                                    </select>
                                                </td>
                                                <td class="py-3 text-right">
                                                    <button @click="deleteActionItem(item.id)" 
                                                            class="text-rose-600 hover:text-rose-800 dark:text-red-400 dark:hover:text-red-300 p-1.5">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                        <tr x-show="actionItems.length === 0">
                                            <td colspan="6" class="text-center py-8 text-slate-400 italic">No action items synced.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB 3: DECISIONS LOG -->
                        <div x-show="activeTab === 'decisions'" class="space-y-6 flex-1">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-purple-500 animate-pulse"></span>
                                    Recorded Decisions
                                </span>
                                <div class="flex items-center gap-2 max-w-sm flex-1 justify-end">
                                    <input type="text" x-model="newDecisionText" placeholder="Record new decision agreement..." 
                                           class="px-3 py-1.5 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs rounded-xl outline-none text-slate-800 dark:text-slate-150 flex-1">
                                    <button @click="saveDecision()" 
                                            class="px-3 py-1.5 bg-skyAccent hover:bg-sky-650 text-white rounded-xl text-xs font-bold shadow whitespace-nowrap">
                                        Log Decision
                                    </button>
                                </div>
                            </div>

                            <!-- Decisions List -->
                            <div class="space-y-3">
                                <template x-for="(decision, index) in decisions" :key="decision.id">
                                    <div class="flex items-start justify-between p-4 bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800/60 rounded-2xl gap-4">
                                        <div class="flex items-start gap-3">
                                            <span class="text-xs font-extrabold text-skyAccent dark:text-blue-400 mt-0.5" x-text="'D' + (index + 1)"></span>
                                            <span class="text-xs text-slate-700 dark:text-slate-300 font-medium whitespace-pre-line leading-relaxed" x-text="decision.decision_text"></span>
                                        </div>
                                        <button @click="deleteDecision(decision.id)" 
                                                class="text-rose-600 hover:text-rose-800 dark:text-red-400 dark:hover:text-red-300 p-1 shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                </template>
                                <div x-show="decisions.length === 0" class="text-center py-8 text-slate-400 italic">No decisions logged.</div>
                            </div>
                        </div>

                        <!-- TAB 4: FULL TRANSCRIPT -->
                        <div x-show="activeTab === 'transcript'" class="space-y-6 flex-1 flex flex-col">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Full Transcribed Script</span>
                                <span class="text-xs text-slate-450 italic" x-text="transcript ? 'Word count: ' + transcript.transcript.split(' ').length : ''"></span>
                            </div>

                            <!-- Transcript Box -->
                            <div class="bg-slate-50 dark:bg-slate-950/40 border border-slate-100 dark:border-slate-800 rounded-2xl p-4 overflow-y-auto max-h-96 text-xs text-slate-650 dark:text-slate-400 font-mono whitespace-pre-line leading-relaxed flex-1"
                                 x-text="transcript ? transcript.transcript : 'Transcript body will load directly from the Fireflies API.'">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Create Action Item Modal -->
    <div x-show="showAddActionModal" 
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/65 backdrop-blur-sm transition-all"
         x-cloak>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 w-full max-w-md rounded-3xl p-6 shadow-xl space-y-6"
             @click.away="showAddActionModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Create Action Item</h3>
                <button @click="showAddActionModal = false" class="text-slate-450 hover:text-slate-650">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="space-y-4 font-sans">
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-450 uppercase">Task Description</label>
                    <textarea x-model="newAction.action_item" rows="3" placeholder="What task needs to be completed?" 
                              class="w-full px-3.5 py-2.5 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs rounded-xl outline-none text-slate-800 dark:text-slate-150"></textarea>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-450 uppercase">Assignee</label>
                    <select x-model="newAction.assigned_to" 
                            class="w-full px-3.5 py-2.5 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs rounded-xl outline-none text-slate-800 dark:text-slate-150">
                        <option value="">Unassigned</option>
                        <template x-for="member in teamMembers" :key="member.id">
                            <option :value="member.id" x-text="member.name"></option>
                        </template>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-450 uppercase">Due Date</label>
                        <input type="date" x-model="newAction.due_date" 
                               class="w-full px-3.5 py-2.5 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs rounded-xl outline-none text-slate-800 dark:text-slate-150">
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-450 uppercase">Priority</label>
                        <select x-model="newAction.priority" 
                                class="w-full px-3.5 py-2.5 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs rounded-xl outline-none text-slate-800 dark:text-slate-150">
                            <option value="High">High</option>
                            <option value="Medium">Medium</option>
                            <option value="Low">Low</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-2.5 font-sans">
                <button @click="showAddActionModal = false" 
                        class="px-4 py-2 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-350 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl text-xs font-bold">
                    Cancel
                </button>
                <button @click="saveActionItem()" 
                        class="px-4 py-2 bg-skyAccent hover:bg-sky-650 text-white rounded-xl text-xs font-bold shadow">
                    Create Task
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function meetingDetails(config) {
    return {
        meetingId: config.id,
        status: config.initialStatus,
        actionItems: config.initialActionItems || [],
        decisions: config.initialDecisions || [],
        transcript: config.initialTranscript || null,
        teamMembers: config.teamMembers || [],
        activeTab: config.initialStatus === 'Completed' ? 'notes' : 'action_items',
        
        // Modal & Inputs state
        showAddActionModal: false,
        newAction: {
            action_item: '',
            assigned_to: '',
            due_date: '',
            priority: 'Medium'
        },
        newDecisionText: '',

        completeMeeting() {
            if (!confirm('Mark this meeting as completed? This will fetch notes from Fireflies.ai.')) return;
            fetch(`/dashboard/meetings/${this.meetingId}/complete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.status = data.meeting.status;
                    this.transcript = data.meeting.transcript;
                    if (data.meeting.action_items) {
                        this.actionItems = data.meeting.action_items.map(item => {
                            return {
                                id: item.id,
                                action_item: item.action_item,
                                assigned_to: item.assigned_to,
                                assignee_name: item.assignee_name || (item.assignee ? item.assignee.name : null),
                                due_date: item.due_date ? item.due_date.substring(0, 10) : null,
                                priority: item.priority,
                                status: item.status
                            };
                        });
                    }
                    if (data.meeting.decisions) {
                        this.decisions = data.meeting.decisions;
                    }
                    this.activeTab = 'notes';
                }
            });
        },

        cancelMeeting() {
            if (!confirm('Are you sure you want to cancel this meeting?')) return;
            fetch(`/dashboard/meetings/${this.meetingId}/cancel`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.status = data.meeting.status;
                }
            });
        },

        syncFireflies() {
            fetch(`/dashboard/meetings/${this.meetingId}/sync-fireflies`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.transcript = data.meeting.transcript;
                    if (data.meeting.action_items) {
                        this.actionItems = data.meeting.action_items.map(item => {
                            return {
                                id: item.id,
                                action_item: item.action_item,
                                assigned_to: item.assigned_to,
                                assignee_name: item.assignee_name || (item.assignee ? item.assignee.name : null),
                                due_date: item.due_date ? item.due_date.substring(0, 10) : null,
                                priority: item.priority,
                                status: item.status
                            };
                        });
                    }
                    if (data.meeting.decisions) {
                        this.decisions = data.meeting.decisions;
                    }
                    alert('Sync complete!');
                }
            });
        },

        updateActionStatus(actionId, status) {
            fetch(`/dashboard/action-items/${actionId}/update`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    let index = this.actionItems.findIndex(i => i.id === actionId);
                    if (index !== -1) {
                        this.actionItems[index].status = data.action_item.status;
                    }
                }
            });
        },

        updateActionAssignee(actionId, assignedTo) {
            fetch(`/dashboard/action-items/${actionId}/update`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ assigned_to: assignedTo })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    let index = this.actionItems.findIndex(i => i.id === actionId);
                    if (index !== -1) {
                        this.actionItems[index].assigned_to = data.action_item.assigned_to;
                        this.actionItems[index].assignee_name = data.action_item.assignee ? data.action_item.assignee.name : null;
                    }
                }
            });
        },

        updateActionDueDate(actionId, dueDate) {
            fetch(`/dashboard/action-items/${actionId}/update`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ due_date: dueDate })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    let index = this.actionItems.findIndex(i => i.id === actionId);
                    if (index !== -1) {
                        this.actionItems[index].due_date = data.action_item.due_date ? data.action_item.due_date.substring(0, 10) : null;
                    }
                }
            });
        },

        updateActionPriority(actionId, priority) {
            fetch(`/dashboard/action-items/${actionId}/update`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ priority })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    let index = this.actionItems.findIndex(i => i.id === actionId);
                    if (index !== -1) {
                        this.actionItems[index].priority = data.action_item.priority;
                    }
                }
            });
        },

        deleteActionItem(actionId) {
            if (!confirm('Are you sure you want to delete this action item?')) return;
            fetch(`/dashboard/action-items/${actionId}/delete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.actionItems = this.actionItems.filter(i => i.id !== actionId);
                }
            });
        },

        saveActionItem() {
            if (!this.newAction.action_item) {
                alert('Task description is required.');
                return;
            }
            fetch(`/dashboard/action-items`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    meeting_id: this.meetingId,
                    ...this.newAction
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.actionItems.push({
                        id: data.action_item.id,
                        action_item: data.action_item.action_item,
                        assigned_to: data.action_item.assigned_to,
                        assignee_name: data.action_item.assignee ? data.action_item.assignee.name : null,
                        due_date: data.action_item.due_date ? data.action_item.due_date.substring(0, 10) : null,
                        priority: data.action_item.priority,
                        status: data.action_item.status
                    });
                    this.showAddActionModal = false;
                    this.newAction = { action_item: '', assigned_to: '', due_date: '', priority: 'Medium' };
                }
            });
        },

        saveDecision() {
            if (!this.newDecisionText) {
                alert('Decision description is required.');
                return;
            }
            fetch(`/dashboard/decisions`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    meeting_id: this.meetingId,
                    decision_text: this.newDecisionText
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.decisions.push({
                        id: data.decision.id,
                        decision_text: data.decision.decision_text
                    });
                    this.newDecisionText = '';
                }
            });
        },

        deleteDecision(decisionId) {
            if (!confirm('Are you sure you want to delete this decision log?')) return;
            fetch(`/dashboard/decisions/${decisionId}/delete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.decisions = this.decisions.filter(d => d.id !== decisionId);
                }
            });
        },

        formatDate(dateStr) {
            if (!dateStr) return 'TBD';
            const d = new Date(dateStr);
            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', timeZone: 'UTC' });
        },

        formatTime(timeStr) {
            if (!timeStr) return 'TBD';
            const parts = timeStr.split(':');
            let hours = parseInt(parts[0]);
            const minutes = parts[1];
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12;
            return `${hours}:${minutes} ${ampm}`;
        }
    };
}
</script>
@endsection
