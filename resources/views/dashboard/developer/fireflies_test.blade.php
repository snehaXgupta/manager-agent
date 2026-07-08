@extends('layouts.app')

@section('content')
<div class="space-y-8 animate-fade-in">
    <!-- Header Nav -->
    <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Fireflies AI Webhook Diagnostics</h2>
            <!-- <p class="text-sm text-slate-500 dark:text-slate-400">Monitor incoming webhooks, inspect signature authorization, and audit processed transcripts.</p> -->
        </div>
        <a href="{{ route('dashboard.developer.index') }}" 
           class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-skyAccent dark:text-slate-400 dark:hover:text-blue-400 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Developer Tools
        </a>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left Side: Fireflies Webhook Status Card -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-5">
                <h3 class="font-bold text-slate-900 dark:text-white flex items-center gap-2 text-sm">
                    <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    Webhook Diagnostics
                </h3>

                <!-- Status Elements -->
                <div class="space-y-4 text-xs font-sans">
                    <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-slate-100 dark:border-slate-850">
                        <span class="text-slate-500 dark:text-slate-400 font-medium">Webhook Status</span>
                        <span class="inline-flex px-2.5 py-0.5 rounded-full font-bold text-[10px] border 
                            {{ str_starts_with($webhookStatus, 'Failed') ? 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/20 dark:text-red-400 dark:border-red-800' : ($webhookStatus === 'Success' ? 'bg-green-50 text-green-700 border-green-200 dark:bg-green-950/20 dark:text-green-400 dark:border-green-800' : 'bg-slate-50 text-slate-500 border-slate-200 dark:bg-slate-800 dark:text-slate-400') }}">
                            {{ $webhookStatus }}
                        </span>
                    </div>

                    <div class="flex flex-col gap-1 p-3 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-slate-100 dark:border-slate-850">
                        <span class="text-slate-550 dark:text-slate-400 font-medium">Webhook URL</span>
                        <span class="font-mono text-[9px] text-slate-700 dark:text-slate-300 break-all select-all font-bold">{{ $webhookUrl }}</span>
                    </div>

                    <div class="flex flex-col gap-1 p-3 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-slate-100 dark:border-slate-850">
                        <span class="text-slate-555 dark:text-slate-400 font-medium">Webhook Secret</span>
                        <span class="font-mono text-[10px] text-slate-700 dark:text-slate-300 truncate select-all font-bold" title="{{ $webhookSecret ?: 'Not configured' }}">
                            {{ $webhookSecret ?: 'Not configured' }}
                        </span>
                    </div>

                    <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-slate-100 dark:border-slate-850">
                        <span class="text-slate-500 dark:text-slate-400 font-medium">Last Webhook Received</span>
                        <span class="font-bold text-slate-800 dark:text-slate-350">{{ $lastWebhookReceived }}</span>
                    </div>

                    <div class="flex flex-col gap-1.5 p-3 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-slate-100 dark:border-slate-850">
                        <span class="text-slate-500 dark:text-slate-400 font-medium">Last Meeting Synced</span>
                        <strong class="text-slate-850 dark:text-white font-bold truncate">
                            {{ $lastMeetingSynced }}
                        </strong>
                    </div>

                    <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-slate-100 dark:border-slate-850">
                        <span class="text-slate-500 dark:text-slate-400 font-medium">Last Transcript Synced ID</span>
                        <span class="font-mono text-[10px] text-slate-750 dark:text-slate-300 truncate max-w-[130px] font-bold" title="{{ $lastTranscriptSynced }}">
                            {{ $lastTranscriptSynced }}
                        </span>
                    </div>
                </div>

                <!-- Simulation Trigger -->
                <div class="pt-4 border-t border-slate-100 dark:border-slate-800/80">
                    <form action="{{ route('dashboard.developer.fireflies.send-test') }}" method="POST">
                        @csrf
                        <button type="submit" 
                                class="w-full text-center px-4 py-2.5 rounded-xl text-xs font-bold bg-skyAccent hover:bg-sky-600 text-white shadow-sm transition-all flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                            Send Test Webhook
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Side: Database Webhook Payload Auditing Logs -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Auditing Logs Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-5">
                <div class="flex items-center justify-between border-b border-slate-150 dark:border-slate-800 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Webhook Payloads Audit Log</h3>
                    <span class="text-xs text-slate-400 font-bold bg-slate-50 dark:bg-slate-800 px-2 py-0.5 rounded">{{ $webhookPayloads->count() }} received</span>
                </div>

                <!-- Webhook Payloads List -->
                <div class="overflow-x-auto font-sans">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-slate-450 text-[10px] font-bold uppercase tracking-wider border-b border-slate-150 dark:border-slate-800 pb-3">
                                <th class="py-2.5">Fireflies Meeting ID</th>
                                <th class="py-2.5">Event Type</th>
                                <th class="py-2.5">Received At</th>
                                <th class="py-2.5">Status</th>
                                <th class="py-2.5 text-right">Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs">
                            @forelse($webhookPayloads as $payload)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                                    <td class="py-3 font-mono font-semibold text-slate-800 dark:text-slate-200 select-all">
                                        {{ $payload->fireflies_meeting_id ?: 'TBD' }}
                                    </td>
                                    <td class="py-3 text-slate-550">{{ $payload->event_type }}</td>
                                    <td class="py-3 text-slate-500">
                                        {{ $payload->created_at->format('M d, Y H:i:s') }}
                                    </td>
                                    <td class="py-3">
                                        @if($payload->processed)
                                            <span class="inline-flex px-2 py-0.5 rounded text-[9px] font-bold bg-green-50 text-green-700 dark:bg-green-950/20 dark:text-green-400 border border-green-200 dark:border-green-800">Processed</span>
                                        @elseif($payload->error)
                                            <span class="inline-flex px-2 py-0.5 rounded text-[9px] font-bold bg-red-50 text-red-700 dark:bg-red-950/20 dark:text-red-400 border border-red-200 dark:border-red-800" title="{{ $payload->error }}">Error</span>
                                        @else
                                            <span class="inline-flex px-2 py-0.5 rounded text-[9px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400 border border-amber-200 dark:border-amber-800">Pending</span>
                                        @endif
                                    </td>
                                    <td class="py-3 text-right">
                                        <button type="button" @click="alert(JSON.stringify({{$payload->payload}}, null, 2))"
                                                class="font-bold text-skyAccent hover:underline dark:text-blue-400 focus:outline-none">Payload</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-8 text-slate-400 italic">No webhook payloads logged yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>
</div>
@endsection
