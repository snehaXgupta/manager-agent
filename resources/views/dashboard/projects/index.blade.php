@extends('layouts.app')

@section('content')
<div class="space-y-8 animate-fade-in" x-data="{ showCreateForm: false, showDeleteModal: false, projectDeleteUrl: '', showEditModal: false, editProjectAction: '', editProjectName: '', editProjectDesc: '', editProjectCategory: 'Development', editProjectStatus: 'active', editProjectDeadline: '', editProjectMembers: [] }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Manager Projects</h2>
            <!-- <p class="text-sm text-slate-500 dark:text-slate-400">Organize your direct reports into projects, assign tasks, and monitor overall delivery progress.</p> -->
        </div>
        <button @click="showCreateForm = !showCreateForm" 
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm bg-skyAccent hover:bg-sky-600 text-white shadow-sm hover:shadow transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Create New Project
        </button>
    </div>

    <!-- Active vs Archived workspace tabs -->
    <div class="flex items-center gap-1 border-b border-slate-200 dark:border-slate-800">
        <a href="{{ route('dashboard.projects.index', ['view_archived' => 0]) }}" 
           class="px-4 py-2.5 text-xs font-bold border-b-2 transition-all {{ !request('view_archived') ? 'border-skyAccent text-skyAccent' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">
            Active Workspaces
        </a>
        <a href="{{ route('dashboard.projects.index', ['view_archived' => 1]) }}" 
           class="px-4 py-2.5 text-xs font-bold border-b-2 transition-all {{ request('view_archived') ? 'border-skyAccent text-skyAccent' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">
            Archived Workspaces
        </a>
    </div>

    <!-- Filters & Search Bar -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
        <form action="{{ route('dashboard.projects.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
            <input type="hidden" name="view_archived" value="{{ request('view_archived', 0) }}">
            
            <!-- Search Project Name/Desc -->
            <div class="flex-1 min-w-[200px] space-y-1">
                <label for="search" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Search Project Name / Description</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Search projects..."
                       class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-xs text-slate-850 dark:text-slate-105">
            </div>

            <!-- Category filter -->
            <div class="w-full sm:w-[150px] space-y-1">
                <label for="category" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Category</label>
                <select name="category" id="category"
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-xs text-slate-850 dark:text-slate-105">
                    <option value="">All Categories</option>
                    <option value="Development" {{ request('category') === 'Development' ? 'selected' : '' }}>Development</option>
                    <option value="Design" {{ request('category') === 'Design' ? 'selected' : '' }}>Design</option>
                    <option value="Infrastructure" {{ request('category') === 'Infrastructure' ? 'selected' : '' }}>Infrastructure</option>
                    <option value="Operations" {{ request('category') === 'Operations' ? 'selected' : '' }}>Operations</option>
                    <option value="Security" {{ request('category') === 'Security' ? 'selected' : '' }}>Security</option>
                </select>
            </div>

            <!-- Status filter -->
            <div class="w-full sm:w-[150px] space-y-1">
                <label for="status" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</label>
                <select name="status" id="status"
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-xs text-slate-850 dark:text-slate-105">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="on_hold" {{ request('status') === 'on_hold' ? 'selected' : '' }}>On Hold</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
            </div>

            <!-- Sort By dropdown -->
            <div class="w-full sm:w-[180px] space-y-1">
                <label for="sort_by" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Sort By</label>
                <select name="sort_by" id="sort_by"
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-xs text-slate-850 dark:text-slate-105">
                    <option value="name_asc" {{ request('sort_by') === 'name_asc' ? 'selected' : '' }}>Name: A-Z</option>
                    <option value="name_desc" {{ request('sort_by') === 'name_desc' ? 'selected' : '' }}>Name: Z-A</option>
                    <option value="tasks_desc" {{ request('sort_by') === 'tasks_desc' ? 'selected' : '' }}>Tasks Count: High to Low</option>
                    <option value="completion_desc" {{ request('sort_by') === 'completion_desc' ? 'selected' : '' }}>Completion Rate: High to Low</option>
                </select>
            </div>

            <!-- Filter Buttons -->
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('dashboard.projects.index', ['view_archived' => request('view_archived', 0)]) }}"
                   class="px-4 py-2.5 border border-slate-200 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl transition-all">
                    Reset
                </a>
                <button type="submit"
                        class="px-4 py-2.5 bg-skyAccent hover:bg-sky-605 text-white text-xs font-bold rounded-xl shadow-sm transition-all whitespace-nowrap">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Create Project Form Card -->
    <div x-cloak x-show="showCreateForm" x-collapse
         class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 shadow-md">
        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-skyAccent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
            Form a New Project
        </h3>
        
        <form action="{{ route('dashboard.projects.store') }}" method="POST" class="space-y-6">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Project Info input -->
                <div class="space-y-4 md:col-span-1">
                    <div class="space-y-2">
                        <label for="name" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Project Name</label>
                        <input type="text" name="name" id="name" required placeholder="e.g. Analytics Platform V2"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-250 dark:border-slate-800 bg-transparent text-sm focus:ring-1 focus:ring-skyAccent focus:border-skyAccent outline-none text-slate-800 dark:text-slate-100">
                    </div>
                    <div class="space-y-2">
                        <label for="description" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Description</label>
                        <textarea name="description" id="description" rows="3" placeholder="Define the project scope..."
                                  class="w-full px-4 py-2.5 rounded-xl border border-slate-250 dark:border-slate-800 bg-transparent text-sm focus:ring-1 focus:ring-skyAccent focus:border-skyAccent outline-none text-slate-800 dark:text-slate-100"></textarea>
                    </div>
                    <div class="space-y-2">
                        <label for="create_category" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Category</label>
                        <select name="category" id="create_category" required
                                class="w-full px-4 py-2.5 border border-slate-250 dark:border-slate-800 bg-white dark:bg-slate-900 text-sm rounded-xl outline-none text-slate-850 dark:text-slate-100 focus:ring-1 focus:ring-skyAccent focus:border-skyAccent">
                            <option value="Development" selected>Development</option>
                            <option value="Design">Design</option>
                            <option value="Infrastructure">Infrastructure</option>
                            <option value="Operations">Operations</option>
                            <option value="Security">Security</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label for="create_deadline" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Target Deadline</label>
                        <input type="date" name="deadline" id="create_deadline"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-250 dark:border-slate-800 bg-transparent text-sm focus:ring-1 focus:ring-skyAccent focus:border-skyAccent outline-none text-slate-800 dark:text-slate-100">
                    </div>

                    <!-- Repository Configuration -->
                    <div class="space-y-3 pt-4 border-t border-slate-150 dark:border-slate-800" x-data="{ repoMode: 'new' }">
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Repository Configuration</label>
                        
                        <div class="flex items-center gap-4 text-xs font-semibold text-slate-700 dark:text-slate-300">
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" name="repo_mode" value="new" x-model="repoMode" checked class="text-skyAccent focus:ring-skyAccent">
                                Create New Repo
                            </label>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" name="repo_mode" value="existing" x-model="repoMode" class="text-skyAccent focus:ring-skyAccent">
                                Link Existing Repo
                            </label>
                        </div>

                        <!-- Existing Repo Fields -->
                        <div x-show="repoMode === 'existing'" class="space-y-3 pt-2 animate-fade-in" x-cloak>
                            <div class="space-y-2">
                                <label for="existing_repo_url" class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Repository URL</label>
                                <input type="url" name="existing_repo_url" id="existing_repo_url" :required="repoMode === 'existing'" placeholder="https://gitlab.com/username/project"
                                        class="w-full px-3.5 py-2 rounded-xl border border-slate-250 dark:border-slate-800 bg-transparent text-xs focus:ring-1 focus:ring-skyAccent focus:border-skyAccent outline-none text-slate-800 dark:text-slate-100">
                            </div>
                            <div class="space-y-2">
                                <label for="existing_gitlab_project_id" class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">GitLab Project ID (Numeric)</label>
                                <input type="number" name="existing_gitlab_project_id" id="existing_gitlab_project_id" :required="repoMode === 'existing'" placeholder="e.g. 12345678"
                                       class="w-full px-3.5 py-2 rounded-xl border border-slate-250 dark:border-slate-800 bg-transparent text-xs focus:ring-1 focus:ring-skyAccent focus:border-skyAccent outline-none text-slate-800 dark:text-slate-100">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Members select -->
                <div class="space-y-2 md:col-span-2" 
                     x-data="{ 
                         searchQuery: '', 
                         suggestions: [], 
                         selectedMembers: [], 
                         loading: false,
                         directReports: {{ $employees->toJson() }},
                         fetchSuggestions() {
                             if (this.searchQuery.length < 2) {
                                 this.suggestions = [];
                                 return;
                             }
                             this.loading = true;
                             fetch(`/dashboard/employees/search?query=${encodeURIComponent(this.searchQuery)}`)
                                     .then(res => res.json())
                                     .then(data => {
                                         // Filter out already selected members from suggestions
                                         this.suggestions = data.filter(s => !this.selectedMembers.some(m => m.id === s.id));
                                         this.loading = false;
                                     })
                                     .catch(err => {
                                         console.error(err);
                                         this.loading = false;
                                     });
                         },
                         addMember(member) {
                             if (!this.selectedMembers.some(m => m.id === member.id)) {
                                 this.selectedMembers.push(member);
                             }
                             this.searchQuery = '';
                             this.suggestions = [];
                         },
                         removeMember(id) {
                             this.selectedMembers = this.selectedMembers.filter(m => m.id !== id);
                         }
                     }">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Select Project Members</label>
                        <button type="button" @click="selectedMembers = [...directReports]" 
                                class="text-xs font-bold text-skyAccent hover:text-sky-600 dark:text-blue-400 dark:hover:text-blue-300 transition-colors focus:outline-none">
                            Select All Members
                        </button>
                    </div>
                    
                    <!-- Search Input Box -->
                    <div class="relative">
                        <input type="text" 
                               x-model="searchQuery" 
                               @input.debounce.300ms="fetchSuggestions()"
                               placeholder="Search team members by name or email (type at least 2 chars)..."
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-250 dark:border-slate-800 bg-transparent text-sm focus:ring-1 focus:ring-skyAccent focus:border-skyAccent outline-none text-slate-800 dark:text-slate-100">
                        
                        <!-- Loading indicator -->
                        <div x-show="loading" class="absolute right-3 top-3">
                            <svg class="animate-spin h-5 w-5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>

                        <!-- Suggestions Dropdown -->
                        <div x-show="suggestions.length > 0" 
                             class="absolute right-0 left-0 z-50 mt-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-lg max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
                            <template x-for="item in suggestions" :key="item.id">
                                <button type="button" 
                                        @click="addMember(item)"
                                        class="w-full text-left px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 flex flex-col transition-colors">
                                    <span class="text-xs font-bold text-slate-850 dark:text-slate-200" x-text="item.name"></span>
                                    <span class="text-[10px] text-slate-450" x-text="item.email"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Hidden Form Inputs for Submission -->
                    <template x-for="member in selectedMembers" :key="member.id">
                        <input type="hidden" name="members[]" :value="member.id">
                    </template>

                    <!-- Selected Members Tags -->
                    <div class="space-y-2 mt-3">
                        <span class="block text-[10px] font-bold text-slate-455 uppercase tracking-wider">Selected Members (<span x-text="selectedMembers.length"></span>)</span>
                        <div class="flex flex-wrap gap-2 p-3 min-h-[50px] border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50/50 dark:bg-slate-900/40">
                            <template x-for="member in selectedMembers" :key="member.id">
                                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-sky-50 dark:bg-blue-950/20 text-skyAccent dark:text-blue-400 rounded-xl border border-sky-100 dark:border-blue-900/30 text-xs font-bold">
                                    <div class="text-left">
                                        <span class="block leading-tight" x-text="member.name"></span>
                                        <span class="block text-[9px] text-slate-400 font-normal leading-none" x-text="member.email"></span>
                                    </div>
                                    <button type="button" @click="removeMember(member.id)" class="text-slate-400 hover:text-red-500 focus:outline-none transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            </template>
                            <div x-show="selectedMembers.length === 0" class="text-xs text-slate-400 italic p-1">
                                No members selected yet. Search above to add team members to this project.
                            </div>
                        </div>
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
                    Confirm & Create
                </button>
            </div>
        </form>
    </div>

    <!-- Projects Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($projects as $project)
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                <div class="space-y-4">
                    <!-- Title/Action -->
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/60 pb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-sky-50 dark:bg-blue-950/20 text-skyAccent dark:text-blue-400 flex items-center justify-center font-bold text-sm">
                                {{ substr($project->name, 0, 1) }}
                            </div>
                            <div>
                                <span class="block font-bold text-slate-900 dark:text-white truncate max-w-[140px]" title="{{ $project->name }}">{{ $project->name }}</span>
                                <span class="inline-block px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 mt-0.5">{{ $project->category ?: 'Development' }}</span>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <!-- Archive/Unarchive Action -->
                            <form action="{{ route('dashboard.projects.archive', $project->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-slate-400 hover:text-amber-500 transition-colors focus:outline-none" title="{{ $project->is_archived ? 'Restore Workspace' : 'Archive Workspace' }}">
                                    @if($project->is_archived)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                                    @endif
                                </button>
                            </form>

                            <!-- Edit Action -->
                            <button type="button" 
                                    @click="
                                        editProjectAction = '{{ route('dashboard.projects.update', $project->id) }}';
                                        editProjectName = '{{ addslashes($project->name) }}';
                                        editProjectDesc = '{{ addslashes($project->description) }}';
                                        editProjectCategory = '{{ $project->category ?: 'Development' }}';
                                        editProjectStatus = '{{ $project->status ?: 'active' }}';
                                        editProjectDeadline = '{{ $project->deadline ? $project->deadline->format('Y-m-d') : '' }}';
                                        editProjectMembers = {{ json_encode($project->members->pluck('id')) }};
                                        showEditModal = true;
                                    "
                                    class="text-slate-400 hover:text-skyAccent transition-colors focus:outline-none" 
                                    title="Edit Project">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h14a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </button>

                            <!-- Dissolve/Delete Action -->
                            <button type="button" 
                                    @click="projectDeleteUrl = '{{ route('dashboard.projects.destroy', $project->id) }}'; showDeleteModal = true;"
                                    class="text-slate-450 hover:text-red-500 transition-colors focus:outline-none" 
                                    title="Dissolve Project">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Description -->
                    <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 h-8">{{ $project->description ?? 'No description.' }}</p>

                    <!-- Status & Deadline & Members -->
                    <div class="space-y-2 text-xs pt-2">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500 font-medium">Status</span>
                            @if($project->status === 'completed')
                                <span class="inline-flex px-2 py-0.5 rounded-md bg-green-50 dark:bg-green-950/20 text-green-700 dark:text-green-400 text-[10px] font-bold uppercase">Completed</span>
                            @elseif($project->status === 'on_hold')
                                <span class="inline-flex px-2 py-0.5 rounded-md bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-400 text-[10px] font-bold uppercase">On Hold</span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded-md bg-sky-50 dark:bg-sky-950/20 text-skyAccent dark:text-sky-400 text-[10px] font-bold uppercase">Active</span>
                            @endif
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500 font-medium">Target Deadline</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200">
                                @if($project->deadline)
                                    {{ $project->deadline->format('M d, Y') }}
                                @else
                                    <span class="text-slate-400 italic">No Target</span>
                                @endif
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500 font-medium font-bold uppercase tracking-wider text-[10px]">Associated Members</span>
                            <span class="font-bold text-slate-850 dark:text-slate-200">{{ $project->members_count }}</span>
                        </div>
                    </div>
                </div>

                <div class="pt-6 border-t border-slate-100 dark:border-slate-800/60 mt-6">
                    <a href="{{ route('dashboard.projects.show', $project->id) }}" 
                       class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 text-slate-600 hover:text-skyAccent hover:border-skyAccent dark:text-slate-400 dark:hover:text-blue-400 dark:hover:border-blue-800 font-bold text-xs transition-colors">
                        Open Project Workspace
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center py-12 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6">
                <div class="w-12 h-12 rounded-2xl bg-sky-50 dark:bg-blue-950/20 text-skyAccent dark:text-blue-400 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
                </div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">No Projects Found</h3>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Get started by creating a new project to bundle employee members, assign tasks, and track performance scores.</p>
                <button @click="showCreateForm = true" class="mt-4 px-4 py-2 rounded-lg bg-skyAccent hover:bg-sky-600 text-white text-xs font-bold transition-all">
                    Form a Project
                </button>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if ($projects->hasPages())
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4">
            {{ $projects->links() }}
        </div>
    @endif

    <!-- Hidden Form for Project Deletion -->
    <form id="delete-project-form" :action="projectDeleteUrl" method="POST" class="hidden">
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
                                Dissolve Project
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-slate-500 dark:text-slate-400">
                                    Are you sure you want to dissolve this project? All associated metadata records will be affected. This action cannot be undone.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-slate-50 dark:bg-slate-900/50 px-6 py-4 sm:px-6 flex flex-row-reverse gap-3 border-t border-slate-100 dark:border-slate-800/60">
                    <button type="button" 
                            @click="document.getElementById('delete-project-form').submit()" 
                            class="px-4 py-2.5 rounded-xl text-sm font-bold bg-red-500 hover:bg-red-600 text-white shadow-sm transition-all focus:outline-none">
                        Dissolve Project
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

    <!-- Custom Edit Project Modal -->
    <div x-cloak 
         x-show="showEditModal" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="edit-modal-title" 
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
            <div class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm transition-opacity" aria-hidden="true" @click="showEditModal = false"></div>

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
                
                <form :action="editProjectAction" method="POST" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    
                    <div class="bg-white dark:bg-slate-900 px-6 pt-6 pb-4 sm:p-6">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4" id="edit-modal-title">
                            Edit Project Settings
                        </h3>
                        
                        <div class="space-y-4">
                            <div class="space-y-2">
                                <label for="edit_name" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Project Name</label>
                                <input type="text" name="name" id="edit_name" required x-model="editProjectName"
                                       class="w-full px-4 py-2.5 rounded-xl border border-slate-250 dark:border-slate-800 bg-transparent text-sm focus:ring-1 focus:ring-skyAccent focus:border-skyAccent outline-none text-slate-800 dark:text-slate-100">
                            </div>
                            <div class="space-y-2">
                                <label for="edit_description" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Description</label>
                                <textarea name="description" id="edit_description" rows="3" x-model="editProjectDesc"
                                          class="w-full px-4 py-2.5 rounded-xl border border-slate-250 dark:border-slate-800 bg-transparent text-sm focus:ring-1 focus:ring-skyAccent focus:border-skyAccent outline-none text-slate-800 dark:text-slate-100"></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label for="edit_category" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Category</label>
                                    <select name="category" id="edit_category" required x-model="editProjectCategory"
                                            class="w-full px-4 py-2.5 border border-slate-250 dark:border-slate-800 bg-white dark:bg-slate-900 text-sm rounded-xl outline-none text-slate-850 dark:text-slate-100 focus:ring-1 focus:ring-skyAccent focus:border-skyAccent">
                                        <option value="Development">Development</option>
                                        <option value="Design">Design</option>
                                        <option value="Infrastructure">Infrastructure</option>
                                        <option value="Operations">Operations</option>
                                        <option value="Security">Security</option>
                                    </select>
                                </div>
                                <div class="space-y-2">
                                    <label for="edit_status" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</label>
                                    <select name="status" id="edit_status" required x-model="editProjectStatus"
                                            class="w-full px-4 py-2.5 border border-slate-250 dark:border-slate-800 bg-white dark:bg-slate-900 text-sm rounded-xl outline-none text-slate-850 dark:text-slate-100 focus:ring-1 focus:ring-skyAccent focus:border-skyAccent">
                                        <option value="active">Active</option>
                                        <option value="on_hold">On Hold</option>
                                        <option value="completed">Completed</option>
                                    </select>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label for="edit_deadline" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Target Deadline</label>
                                <input type="date" name="deadline" id="edit_deadline" x-model="editProjectDeadline"
                                       class="w-full px-4 py-2.5 rounded-xl border border-slate-250 dark:border-slate-800 bg-transparent text-sm focus:ring-1 focus:ring-skyAccent focus:border-skyAccent outline-none text-slate-800 dark:text-slate-100">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Project Members</label>
                                <div class="grid grid-cols-2 gap-2 max-h-36 overflow-y-auto p-3 border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-slate-900">
                                    @foreach($employees as $employee)
                                        <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-350 cursor-pointer">
                                            <input type="checkbox" name="members[]" value="{{ $employee->id }}" 
                                                   :checked="editProjectMembers.includes({{ $employee->id }})"
                                                   @change="if ($el.checked) { if(!editProjectMembers.includes({{ $employee->id }})) editProjectMembers.push({{ $employee->id }}); } else { editProjectMembers = editProjectMembers.filter(id => id !== {{ $employee->id }}); }"
                                                   class="rounded text-skyAccent focus:ring-skyAccent dark:bg-slate-800 dark:border-slate-700">
                                            {{ $employee->name }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-slate-50 dark:bg-slate-900/50 px-6 py-4 sm:px-6 flex flex-row-reverse gap-3 border-t border-slate-100 dark:border-slate-800/60">
                        <button type="submit" 
                                class="px-4 py-2.5 rounded-xl text-sm font-bold bg-skyAccent hover:bg-sky-600 text-white shadow-sm transition-all focus:outline-none">
                            Save Changes
                        </button>
                        <button type="button" 
                                @click="showEditModal = false" 
                                class="px-4 py-2.5 rounded-xl text-sm font-semibold border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 transition-colors focus:outline-none">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
