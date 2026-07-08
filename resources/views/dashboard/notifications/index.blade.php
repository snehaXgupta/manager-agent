@extends('layouts.app')

@section('content')
<div class="space-y-6 animate-fade-in">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Notification Center</h2>
            <!-- <p class="text-sm text-slate-500 dark:text-slate-400">View and manage predictive intelligence alerts and recommendations for your team.</p> -->
        </div>
        
        @if ($notifications->where('is_read', false)->isNotEmpty())
            <form action="{{ route('dashboard.notifications.read-all') }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 bg-slate-100 hover:bg-skyAccent hover:text-white dark:bg-slate-800 dark:hover:bg-blue-500 text-slate-700 dark:text-slate-350 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 hover:border-transparent transition-all">
                    Mark All as Read
                </button>
            </form>
        @endif
    </div>

    <!-- Notification Feed -->
    <div class="space-y-4">
        @forelse ($notifications as $notification)
            <div class="border rounded-2xl p-5 shadow-sm transition-all flex items-start gap-4 
                @if(!$notification->is_read)
                    bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 ring-1 ring-sky-50 dark:ring-blue-950/20
                @else
                    bg-slate-50/50 dark:bg-slate-900/50 border-slate-200/60 dark:border-slate-800/60 opacity-75
                @endif">
                
                <!-- Severity Indicator Dot -->
                <div class="w-2.5 h-2.5 rounded-full mt-2 shrink-0
                    @if($notification->severity === 'CRITICAL')
                        bg-red-500
                    @elseif($notification->severity === 'WARNING')
                        bg-amber-500
                    @else
                        bg-blue-500
                    @endif">
                </div>

                <div class="flex-1 space-y-1">
                    <div class="flex items-baseline justify-between gap-4">
                        <h4 class="font-bold text-sm text-slate-900 dark:text-white">{{ $notification->title }}</h4>
                        <span class="text-[10px] text-slate-400 shrink-0 font-medium">{{ $notification->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="text-sm text-slate-600 dark:text-slate-350 leading-relaxed">{{ $notification->message }}</p>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-10 text-center space-y-3 shadow-sm">
                <div class="w-12 h-12 rounded-full bg-slate-50 dark:bg-slate-800 text-slate-400 dark:text-slate-500 flex items-center justify-center mx-auto">
                    <!-- Bell-off icon -->
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                </div>
                <div>
                    <h4 class="font-bold text-slate-900 dark:text-white">Inbox is empty</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400">You have no new alerts or predictive updates at this time.</p>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection
