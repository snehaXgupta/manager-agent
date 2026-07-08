@extends('layouts.app')

@section('content')
<div class="space-y-8 animate-fade-in" x-data="{ activeTab: 'accounts', role: 'employee' }">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">User Administration Panel</h2>
        </div>
        <div class="px-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
            <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">Admin Portal Active</span>
        </div>
    </div>

    <!-- Tabs Header -->
    <div class="border-b border-slate-200 dark:border-slate-800">
        <nav class="flex space-x-8" aria-label="Tabs">
            <button @click="activeTab = 'accounts'" 
                    :class="activeTab === 'accounts' ? 'border-skyAccent text-skyAccent dark:border-blue-450 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'"
                    class="py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 focus:outline-none transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                User Accounts
            </button>
            <button @click="activeTab = 'departments'" 
                    :class="activeTab === 'departments' ? 'border-skyAccent text-skyAccent dark:border-blue-450 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'"
                    class="py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 focus:outline-none transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                Departments
            </button>
            <button @click="activeTab = 'designations'" 
                    :class="activeTab === 'designations' ? 'border-skyAccent text-skyAccent dark:border-blue-450 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'"
                    class="py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 focus:outline-none transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                Designations
            </button>
            <button @click="activeTab = 'skills'" 
                    :class="activeTab === 'skills' ? 'border-skyAccent text-skyAccent dark:border-blue-450 dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'"
                    class="py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 focus:outline-none transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364.364l-.707.707M21 12h-1M4 9H3m15.364 6.364l-.707-.707M6.343 6.343l.707-.707m9.9 5.05a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                Skills Directory
            </button>
        </nav>
    </div>

    <!-- TAB 1: ACCOUNTS -->
    <div x-show="activeTab === 'accounts'" class="space-y-8">
        <!-- Counters Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <!-- Total Users -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm flex items-center gap-4">
                <div class="p-3 bg-blue-50 dark:bg-blue-950/20 rounded-xl text-skyAccent dark:text-sky-450">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
                <div>
                    <span class="block text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Accounts</span>
                    <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ $users->count() }} Users</span>
                </div>
            </div>

            <!-- Managers & Team Leads -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm flex items-center gap-4">
                <div class="p-3 bg-violet-50 dark:bg-violet-950/20 rounded-xl text-violet-500 dark:text-violet-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <div>
                    <span class="block text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Managers / Leads</span>
                    <span class="text-2xl font-bold text-slate-900 dark:text-white">
                        {{ $users->where('role', 'manager')->count() }}M / {{ $users->where('role', 'team_lead')->count() }}TL
                    </span>
                </div>
            </div>

            <!-- Employees -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm flex items-center gap-4">
                <div class="p-3 bg-emerald-50 dark:bg-emerald-950/20 rounded-xl text-emerald-500 dark:text-emerald-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </div>
                <div>
                    <span class="block text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Employees</span>
                    <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ $users->where('role', 'employee')->count() }} Active</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Register Form Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm lg:col-span-1 h-fit">
                <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3 mb-4">Create New Account</h3>
                
                <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-4">
                    @csrf
                    
                    <!-- Name -->
                    <div class="space-y-1">
                        <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Full Name</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g. John Doe"
                               class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-sm text-slate-900 dark:text-white">
                        @error('name')
                            <span class="text-xs text-red-500 font-semibold mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div class="space-y-1">
                        <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Email Address (@gmail.com or @company.com)</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="e.g. name@company.com"
                               class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-sm text-slate-900 dark:text-white">
                        @error('email')
                            <span class="text-xs text-red-500 font-semibold mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="space-y-1">
                        <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Password</label>
                        <input type="password" id="password" name="password" required placeholder="Min 6 characters"
                               class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-sm text-slate-900 dark:text-white">
                        @error('password')
                            <span class="text-xs text-red-500 font-semibold mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Role -->
                    <div class="space-y-1">
                        <label for="role" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Role</label>
                        <select id="role" name="role" x-model="role" required
                                class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-sm text-slate-900 dark:text-white">
                            <option value="employee">Employee</option>
                            <option value="team_lead">Team Lead</option>
                            <option value="manager">Manager</option>
                        </select>
                        @error('role')
                            <span class="text-xs text-red-500 font-semibold mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Supervisor Manager (Shown for Employee & Team Lead) -->
                    <div class="space-y-1" x-show="role === 'employee' || role === 'team_lead'">
                        <label for="manager_id" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Assign Supervisor Manager</label>
                        <select id="manager_id" name="manager_id"
                                class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-sm text-slate-900 dark:text-white">
                            <option value="">-- Select Manager --</option>
                            @foreach ($managers as $mgr)
                                <option value="{{ $mgr->id }}">{{ $mgr->name }} ({{ $mgr->email }})</option>
                            @endforeach
                        </select>
                        @error('manager_id')
                            <span class="text-xs text-red-500 font-semibold mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Department Selection -->
                    <div class="space-y-1">
                        <label for="department_id" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Department</label>
                        <select id="department_id" name="department_id"
                                class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-sm text-slate-900 dark:text-white">
                            <option value="">-- Select Department --</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id')
                            <span class="text-xs text-red-500 font-semibold mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Designation Selection -->
                    <div class="space-y-1">
                        <label for="designation_id" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Designation</label>
                        <select id="designation_id" name="designation_id"
                                class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-sm text-slate-900 dark:text-white">
                            <option value="">-- Select Designation --</option>
                            @foreach ($designations as $desig)
                                <option value="{{ $desig->id }}">{{ $desig->name }}</option>
                            @endforeach
                        </select>
                        @error('designation_id')
                            <span class="text-xs text-red-500 font-semibold mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" 
                            class="w-full py-3 bg-skyAccent hover:bg-sky-500 dark:bg-blue-600 dark:hover:bg-blue-500 text-white font-bold rounded-xl shadow-md hover:shadow active:scale-[0.98] transition-all text-xs">
                        Create User Account
                    </button>
                </form>
            </div>

            <!-- Directory Table Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm lg:col-span-2 overflow-hidden flex flex-col">
                <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3 mb-4">User Directory</h3>
                
                <div class="overflow-x-auto flex-1">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-800 text-slate-500 text-xs font-bold uppercase">
                                <th class="py-3 px-4">Name</th>
                                <th class="py-3 px-4">Email</th>
                                <th class="py-3 px-4">Role</th>
                                <th class="py-3 px-4">Dept / Desig</th>
                                <th class="py-3 px-4">Supervisor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($users as $usr)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                    <td class="py-4 px-4 font-bold text-slate-800 dark:text-slate-200">{{ $usr->name }}</td>
                                    <td class="py-4 px-4 text-slate-500 dark:text-slate-400">{{ $usr->email }}</td>
                                    <td class="py-4 px-4">
                                        @if ($usr->role === 'admin')
                                            <span class="inline-flex px-2 py-0.5 rounded-md bg-purple-50 dark:bg-purple-950/20 text-purple-750 dark:text-purple-400 text-[10px] font-bold border border-purple-200 dark:border-purple-800">Admin</span>
                                        @elseif ($usr->role === 'manager')
                                            <span class="inline-flex px-2 py-0.5 rounded-md bg-violet-50 dark:bg-violet-950/20 text-violet-750 dark:text-violet-400 text-[10px] font-bold border border-violet-200 dark:border-violet-800">Manager</span>
                                        @elseif ($usr->role === 'team_lead')
                                            <span class="inline-flex px-2 py-0.5 rounded-md bg-amber-50 dark:bg-amber-950/20 text-amber-750 dark:text-amber-400 text-[10px] font-bold border border-amber-200 dark:border-amber-850">Team Lead</span>
                                        @else
                                            <span class="inline-flex px-2 py-0.5 rounded-md bg-sky-50 dark:bg-sky-950/20 text-skyAccent dark:text-sky-400 text-[10px] font-bold border border-sky-200 dark:border-sky-850">Employee</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-4 text-xs text-slate-600 dark:text-slate-355">
                                        @if ($usr->department || $usr->designation)
                                            <span class="font-medium block text-slate-850 dark:text-slate-200">{{ $usr->department ? $usr->department->name : '-' }}</span>
                                            <span class="block text-slate-400 mt-0.5">{{ $usr->designation ? $usr->designation->name : '-' }}</span>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-4 text-slate-600 dark:text-slate-350">
                                        @if ($usr->manager)
                                            <span class="font-medium">{{ $usr->manager->name }}</span>
                                        @else
                                            <span class="text-xs text-slate-400 dark:text-slate-550">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: DEPARTMENTS -->
    <div x-show="activeTab === 'departments'" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Create Dept Form -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm lg:col-span-1 h-fit">
            <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3 mb-4">Add Department</h3>
            <form action="{{ route('admin.departments.store') }}" method="POST" class="space-y-4">
                @csrf
                <div class="space-y-1">
                    <label for="dept_name" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Department Name</label>
                    <input type="text" id="dept_name" name="name" required placeholder="e.g. Engineering"
                           class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-sm text-slate-900 dark:text-white">
                </div>
                <div class="space-y-1">
                    <label for="dept_desc" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Description</label>
                    <textarea id="dept_desc" name="description" placeholder="Brief details about the department..." rows="3"
                              class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-sm text-slate-900 dark:text-white"></textarea>
                </div>
                <button type="submit" 
                        class="w-full py-3 bg-skyAccent hover:bg-sky-500 dark:bg-blue-600 dark:hover:bg-blue-500 text-white font-bold rounded-xl shadow-md text-xs">
                    Create Department
                </button>
            </form>
        </div>

        <!-- Depts List -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm lg:col-span-2 flex flex-col"
             x-data="{ editingId: null, editingName: '', editingDesc: '' }">
            <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3 mb-4">Department Master</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800 text-slate-500 text-xs font-bold uppercase">
                            <th class="py-3 px-4">Name</th>
                            <th class="py-3 px-4">Description</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($departments as $dept)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <!-- View Mode -->
                                <td class="py-4 px-4 font-bold text-slate-850 dark:text-slate-200" x-show="editingId !== {{ $dept->id }}">
                                    {{ $dept->name }}
                                </td>
                                <td class="py-4 px-4 text-slate-500 dark:text-slate-400" x-show="editingId !== {{ $dept->id }}">
                                    {{ $dept->description ?: '-' }}
                                </td>
                                
                                <!-- Edit Mode -->
                                <td class="py-3 px-4" x-show="editingId === {{ $dept->id }}" colspan="2">
                                    <form :id="'edit-dept-form-' + {{ $dept->id }}" action="{{ route('admin.departments.update', $dept->id) }}" method="POST" class="flex gap-2 items-center">
                                        @csrf
                                        @method('PATCH')
                                        <input type="text" name="name" x-model="editingName" required class="px-2 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white focus:outline-none focus:border-skyAccent">
                                        <input type="text" name="description" x-model="editingDesc" class="flex-1 px-2 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white focus:outline-none focus:border-skyAccent" placeholder="Description">
                                    </form>
                                </td>

                                <td class="py-4 px-4 text-right">
                                    <div class="flex gap-2 justify-end" x-show="editingId !== {{ $dept->id }}">
                                        <button @click="editingId = {{ $dept->id }}; editingName = '{{ addslashes($dept->name) }}'; editingDesc = '{{ addslashes($dept->description) }}';"
                                                class="px-2 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 rounded-lg text-[10px] font-bold text-slate-650 dark:text-slate-300">
                                            Edit
                                        </button>
                                        <form action="{{ route('admin.departments.destroy', $dept->id) }}" method="POST" onsubmit="return confirm('Delete this department?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2 py-1 bg-red-50 hover:bg-red-100 dark:bg-red-950/20 text-red-650 dark:text-red-400 rounded-lg text-[10px] font-bold border border-red-200 dark:border-red-900">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                    <div class="flex gap-2 justify-end" x-show="editingId === {{ $dept->id }}">
                                        <button type="submit" :form="'edit-dept-form-' + {{ $dept->id }}" class="px-2 py-1 bg-green-600 hover:bg-green-505 text-white rounded-lg text-[10px] font-bold">
                                            Save
                                        </button>
                                        <button @click="editingId = null" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-650 dark:text-slate-300 rounded-lg text-[10px] font-bold">
                                            Cancel
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-8 text-center text-slate-400 text-xs">No departments configured yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 3: DESIGNATIONS -->
    <div x-show="activeTab === 'designations'" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Create Desig Form -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm lg:col-span-1 h-fit">
            <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3 mb-4">Add Designation</h3>
            <form action="{{ route('admin.designations.store') }}" method="POST" class="space-y-4">
                @csrf
                <div class="space-y-1">
                    <label for="desig_name" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Designation Name</label>
                    <input type="text" id="desig_name" name="name" required placeholder="e.g. Senior Software Engineer"
                           class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-sm text-slate-900 dark:text-white">
                </div>
                <div class="space-y-1">
                    <label for="desig_desc" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Description</label>
                    <textarea id="desig_desc" name="description" placeholder="Brief details about the designation..." rows="3"
                              class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-sm text-slate-900 dark:text-white"></textarea>
                </div>
                <button type="submit" 
                        class="w-full py-3 bg-skyAccent hover:bg-sky-500 dark:bg-blue-600 dark:hover:bg-blue-500 text-white font-bold rounded-xl shadow-md text-xs">
                    Create Designation
                </button>
            </form>
        </div>

        <!-- Desig List -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm lg:col-span-2 flex flex-col"
             x-data="{ editingId: null, editingName: '', editingDesc: '' }">
            <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3 mb-4">Designation Master</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800 text-slate-500 text-xs font-bold uppercase">
                            <th class="py-3 px-4">Name</th>
                            <th class="py-3 px-4">Description</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($designations as $desig)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <!-- View Mode -->
                                <td class="py-4 px-4 font-bold text-slate-850 dark:text-slate-200" x-show="editingId !== {{ $desig->id }}">
                                    {{ $desig->name }}
                                </td>
                                <td class="py-4 px-4 text-slate-500 dark:text-slate-400" x-show="editingId !== {{ $desig->id }}">
                                    {{ $desig->description ?: '-' }}
                                </td>
                                
                                <!-- Edit Mode -->
                                <td class="py-3 px-4" x-show="editingId === {{ $desig->id }}" colspan="2">
                                    <form :id="'edit-desig-form-' + {{ $desig->id }}" action="{{ route('admin.designations.update', $desig->id) }}" method="POST" class="flex gap-2 items-center">
                                        @csrf
                                        @method('PATCH')
                                        <input type="text" name="name" x-model="editingName" required class="px-2 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white focus:outline-none focus:border-skyAccent">
                                        <input type="text" name="description" x-model="editingDesc" class="flex-1 px-2 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white focus:outline-none focus:border-skyAccent" placeholder="Description">
                                    </form>
                                </td>

                                <td class="py-4 px-4 text-right">
                                    <div class="flex gap-2 justify-end" x-show="editingId !== {{ $desig->id }}">
                                        <button @click="editingId = {{ $desig->id }}; editingName = '{{ addslashes($desig->name) }}'; editingDesc = '{{ addslashes($desig->description) }}';"
                                                class="px-2 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 rounded-lg text-[10px] font-bold text-slate-650 dark:text-slate-300">
                                            Edit
                                        </button>
                                        <form action="{{ route('admin.designations.destroy', $desig->id) }}" method="POST" onsubmit="return confirm('Delete this designation?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2 py-1 bg-red-50 hover:bg-red-100 dark:bg-red-950/20 text-red-650 dark:text-red-400 rounded-lg text-[10px] font-bold border border-red-200 dark:border-red-900">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                    <div class="flex gap-2 justify-end" x-show="editingId === {{ $desig->id }}">
                                        <button type="submit" :form="'edit-desig-form-' + {{ $desig->id }}" class="px-2 py-1 bg-green-600 hover:bg-green-505 text-white rounded-lg text-[10px] font-bold">
                                            Save
                                        </button>
                                        <button @click="editingId = null" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-650 dark:text-slate-300 rounded-lg text-[10px] font-bold">
                                            Cancel
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-8 text-center text-slate-400 text-xs">No designations configured yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 4: SKILLS -->
    <div x-show="activeTab === 'skills'" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Create Skill Form -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm lg:col-span-1 h-fit">
            <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3 mb-4">Add Skill</h3>
            <form action="{{ route('admin.skills.store') }}" method="POST" class="space-y-4">
                @csrf
                <div class="space-y-1">
                    <label for="skill_name" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Skill Name</label>
                    <input type="text" id="skill_name" name="name" required placeholder="e.g. Laravel"
                           class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-sm text-slate-900 dark:text-white">
                </div>
                <button type="submit" 
                        class="w-full py-3 bg-skyAccent hover:bg-sky-500 dark:bg-blue-600 dark:hover:bg-blue-500 text-white font-bold rounded-xl shadow-md text-xs">
                    Create Skill
                </button>
            </form>
        </div>

        <!-- Skills Directory List -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm lg:col-span-2 flex flex-col">
            <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3 mb-4">Master Skills Directory</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800 text-slate-500 text-xs font-bold uppercase">
                            <th class="py-3 px-4">Name</th>
                            <th class="py-3 px-4">Mapped Employees</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($skills as $skill)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="py-4 px-4 font-bold text-slate-850 dark:text-slate-200">
                                    {{ $skill->name }}
                                </td>
                                <td class="py-4 px-4 text-slate-550">
                                    <span class="inline-flex px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold">
                                        {{ $skill->users()->count() }} users
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-right">
                                    <form action="{{ route('admin.skills.destroy', $skill->id) }}" method="POST" onsubmit="return confirm('Delete this skill?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2 py-1 bg-red-50 hover:bg-red-100 dark:bg-red-950/20 text-red-650 dark:text-red-400 rounded-lg text-[10px] font-bold border border-red-200 dark:border-red-900">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-8 text-center text-slate-400 text-xs">No skills configured yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
