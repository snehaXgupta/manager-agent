@extends('layouts.app')

@section('content')
<div class="space-y-8 animate-fade-in" x-data="{ showCreateForm: false, showDeleteModal: false, teamDeleteUrl: '' }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Operational Teams</h2>
            <!-- <p class="text-sm text-slate-500 dark:text-slate-400">Organize your supervised employees into teams, schedule meetings, and analyze team health using AI.</p> -->
        </div>
        <button @click="showCreateForm = !showCreateForm" 
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm bg-skyAccent hover:bg-sky-650 text-white shadow-sm hover:shadow transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Form New Team
        </button>
    </div>

    <!-- Filters & Search Bar -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
        <form action="{{ route('dashboard.teams.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
            <!-- Search Team Name -->
            <div class="flex-1 min-w-[200px] space-y-1">
                <label for="search" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Search Team Name</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Search teams..."
                       class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-xs text-slate-850 dark:text-slate-105">
            </div>

            <!-- Members filter -->
            <div class="w-full sm:w-[150px] space-y-1">
                <label for="members" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Member Count</label>
                <select name="members" id="members"
                        class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-xs text-slate-850 dark:text-slate-105">
                    <option value="">All Teams</option>
                    <option value="empty" {{ request('members') === 'empty' ? 'selected' : '' }}>No members</option>
                    <option value="small" {{ request('members') === 'small' ? 'selected' : '' }}>1 to 5 members</option>
                    <option value="large" {{ request('members') === 'large' ? 'selected' : '' }}>6+ members</option>
                </select>
            </div>

            <!-- Sort By dropdown -->
            <div class="w-full sm:w-[170px] space-y-1">
                <label for="sort_by" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Sort By</label>
                <select name="sort_by" id="sort_by"
                        class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-xs text-slate-850 dark:text-slate-105">
                    <option value="name_asc" {{ request('sort_by') === 'name_asc' ? 'selected' : '' }}>Name: A-Z</option>
                    <option value="name_desc" {{ request('sort_by') === 'name_desc' ? 'selected' : '' }}>Name: Z-A</option>
                    <option value="members_desc" {{ request('sort_by') === 'members_desc' ? 'selected' : '' }}>Members: High to Low</option>
                    <option value="members_asc" {{ request('sort_by') === 'members_asc' ? 'selected' : '' }}>Members: Low to High</option>
                </select>
            </div>

            <!-- Filter Buttons -->
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('dashboard.teams.index') }}"
                   class="px-4 py-2 border border-slate-200 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl transition-all">
                    Reset
                </a>
                <button type="submit"
                        class="px-4 py-2 bg-skyAccent hover:bg-sky-655 text-white text-xs font-bold rounded-xl shadow-sm transition-all whitespace-nowrap">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Create Team Form Card (Alpine driven) -->
    <div x-cloak x-show="showCreateForm" x-collapse
         class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 shadow-md">
        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-skyAccent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            Form a New Team
        </h3>
        
        <form action="{{ route('dashboard.teams.store') }}" method="POST" class="space-y-6">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Team Name input -->
                <div class="space-y-2 md:col-span-1">
                    <label for="name" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Team Name</label>
                    <input type="text" name="name" id="name" required placeholder="e.g. Frontend Squad"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-250 dark:border-slate-800 bg-transparent text-sm focus:ring-1 focus:ring-skyAccent focus:border-skyAccent outline-none text-slate-800 dark:text-slate-100">
                </div>

                <!-- Members select -->
                <div class="space-y-2 md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Select Team Members</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-48 overflow-y-auto p-1 border border-slate-200 dark:border-slate-800 rounded-xl">
                        @forelse ($employees as $employee)
                            <label class="flex items-center gap-3 p-3 rounded-lg border border-slate-100 dark:border-slate-800/40 hover:bg-slate-50 dark:hover:bg-slate-800/30 cursor-pointer transition-colors">
                                <input type="checkbox" name="members[]" value="{{ $employee->id }}" 
                                       class="rounded text-skyAccent focus:ring-skyAccent border-slate-300">
                                <div>
                                    <span class="block text-xs font-bold text-slate-850 dark:text-slate-200">{{ $employee->name }}</span>
                                    <span class="block text-[10px] text-slate-450">{{ $employee->email }}</span>
                                </div>
                            </label>
                        @empty
                            <div class="col-span-2 p-4 text-center text-xs text-slate-405 italic">
                                No eligible team members. Seed the database.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="showCreateForm = false" 
                        class="px-4 py-2.5 rounded-xl text-sm font-semibold border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2.5 rounded-xl text-sm font-bold bg-skyAccent hover:bg-sky-650 text-white shadow-sm transition-all">
                    Confirm & Form Team
                </button>
            </div>
        </form>
    </div>

    <!-- Teams Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($teams as $team)
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                <div class="space-y-4">
                    <!-- Title/Action -->
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/60 pb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-sky-50 dark:bg-blue-950/20 text-skyAccent dark:text-blue-400 flex items-center justify-center font-bold text-sm shrink-0">
                                {{ substr($team->name, 0, 1) }}
                            </div>
                            <div>
                                <span class="font-bold text-slate-900 dark:text-white block">{{ $team->name }}</span>
                                @php
                                    $teamLead = $team->members->map(fn($m) => $m->manager)->first(fn($mgr) => $mgr && $mgr->role === 'team_lead');
                                @endphp
                                @if($teamLead)
                                    <span class="text-[10px] text-skyAccent dark:text-blue-400 font-bold block mt-0.5">Lead: {{ $teamLead->name }}</span>
                                @else
                                    <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold block mt-0.5">Manager: {{ $team->manager->name }}</span>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Dissolve/Delete Action -->
                        <button type="button" 
                                @click="teamDeleteUrl = '{{ route('dashboard.teams.destroy', $team->id) }}'; showDeleteModal = true;"
                                class="text-slate-450 hover:text-red-500 transition-colors focus:outline-none" 
                                title="Dissolve Team">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>

                    <!-- Members avatars/list -->
                    <div class="space-y-2">
                        <span class="block text-[10px] font-bold text-slate-455 uppercase tracking-wider">Members ({{ $team->members_count }})</span>
                        <div class="flex flex-wrap gap-1.5 max-h-24 overflow-y-auto">
                            @forelse ($team->members as $member)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-50 border border-slate-150 text-slate-700 dark:bg-slate-800 dark:border-slate-750 dark:text-slate-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-skyAccent"></span>
                                    {{ $member->name }}
                                </span>
                            @empty
                                <span class="text-xs text-slate-400 italic">No members assigned to this team.</span>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="pt-6 border-t border-slate-100 dark:border-slate-800/60 mt-6">
                    <a href="{{ route('dashboard.teams.show', $team->id) }}" 
                       class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 text-slate-600 hover:text-skyAccent hover:border-skyAccent dark:text-slate-400 dark:hover:text-blue-400 dark:hover:border-blue-800 font-bold text-xs transition-colors">
                        View Team Dashboard
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center py-12 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6">
                <div class="w-12 h-12 rounded-2xl bg-sky-50 dark:bg-blue-950/20 text-skyAccent dark:text-blue-400 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">No Teams Formed Yet</h3>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Get started by forming a new operational team to bundle team members, assign tasks, and track reports.</p>
                <button @click="showCreateForm = true" class="mt-4 px-4 py-2 rounded-lg bg-skyAccent hover:bg-sky-650 text-white text-xs font-bold transition-all">
                    Form a Team
                </button>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if ($teams->hasPages())
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4">
            {{ $teams->links() }}
        </div>
    @endif

    <!-- Hidden Form for Team Deletion -->
    <form id="delete-team-form" :action="teamDeleteUrl" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>

    <!-- Custom Delete Confirmation Modal -->
    <div x-cloak 
         x-show="showDeleteModal" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-title" 
         role="dialog" 
         aria-modal="true"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <!-- Backdrop -->
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm transition-opacity" aria-hidden="true" @click="showDeleteModal = false"></div>

            <!-- Trick browser to center content -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal Panel -->
            <div class="relative inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 dark:border-slate-800"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                
                <div class="bg-white dark:bg-slate-900 px-6 pt-6 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-2xl bg-red-50 dark:bg-red-950/30 text-red-500 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white" id="modal-title">
                                Dissolve Team
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-slate-500 dark:text-slate-400">
                                    Are you sure you want to dissolve this operational team? All associated metadata records will be affected. This action cannot be undone.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-slate-50 dark:bg-slate-900/50 px-6 py-4 sm:px-6 flex flex-row-reverse gap-3 border-t border-slate-100 dark:border-slate-800/60">
                    <button type="button" 
                            @click="document.getElementById('delete-team-form').submit()" 
                            class="px-4 py-2.5 rounded-xl text-sm font-bold bg-red-500 hover:bg-red-655 text-white shadow-sm transition-all focus:outline-none">
                        Dissolve Team
                    </button>
                    <button type="button" 
                            @click="showDeleteModal = false" 
                            class="px-4 py-2.5 rounded-xl text-sm font-semibold border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 transition-colors focus:outline-none">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
