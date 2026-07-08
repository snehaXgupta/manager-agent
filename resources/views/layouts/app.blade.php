<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Manager Dashboard - {{ config('app.name', 'Laravel') }}</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Configure Tailwind for Dark Mode class and custom themes -->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        skyAccent: '#0ea5e9', // Sky Blue for Light Mode
                        navyAccent: '#1e3a8a', // Navy Blue for Dark Mode
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Chart.js for AI Widget Inline Visualizations -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        
        /* Decrease root font size by one point */
        html {
            font-size: 15px;
        }

        /* Custom premium scrollbars */
        * {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }
        .dark * {
            scrollbar-color: #334155 transparent;
        }
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }
        .dark ::-webkit-scrollbar-thumb {
            background: #334155;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        .dark ::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }

        /* Selection styling */
        ::selection {
            background: rgba(14, 165, 233, 0.15);
            color: #0ea5e9;
        }
        .dark ::selection {
            background: rgba(30, 58, 138, 0.4);
            color: #38bdf8;
        }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 min-h-screen font-sans flex flex-col md:flex-row" 
      x-data="{ 
          darkMode: localStorage.getItem('darkMode') === 'true', 
          mobileSidebarOpen: false,
          init() {
              if (this.darkMode) {
                  document.documentElement.classList.add('dark');
              } else {
                  document.documentElement.classList.remove('dark');
              }
          },
          toggleDarkMode() {
              this.darkMode = !this.darkMode;
              localStorage.setItem('darkMode', this.darkMode);
              if (this.darkMode) {
                  document.documentElement.classList.add('dark');
              } else {
                  document.documentElement.classList.remove('dark');
              }
          }
      }">

    @php
        $unreadNotificationsCount = 0;
        $activeRisksCount = 0;
        $recentNotifications = collect();
        if (auth()->check()) {
            $unreadNotificationsCount = \App\Models\Notification::where('user_id', auth()->id())->where('is_read', false)->count();
            $recentNotifications = \App\Models\Notification::where('user_id', auth()->id())->orderBy('created_at', 'desc')->take(5)->get();
            $activeRisksCount = \App\Models\RiskAlert::where('is_resolved', false)
                ->whereIn('employee_id', function ($query) {
                    $query->select('id')->from('users')->where('manager_id', auth()->id());
                })
                ->count();

            // AI Chat Data JSON Compiling
            $widgetMessagesJson = json_encode(isset($activeConversation) && $activeConversation ? $activeConversation->messages->map(function($msg) {
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

            $widgetConversationsJson = json_encode(isset($conversations) ? $conversations->map(function($conv) {
                return [
                    'id' => $conv->id,
                    'title' => $conv->title,
                    'updated_at' => $conv->updated_at->toDateTimeString()
                ];
            }) : []);

            $widgetSuggestedQuestionsJson = json_encode($suggestedQuestions ?? []);
        }
    @endphp

    <!-- Mobile Header -->
    <header class="flex items-center justify-between px-6 py-4 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 md:hidden w-full shrink-0">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-skyAccent dark:bg-navyAccent flex items-center justify-center text-white font-bold text-lg">
                M
            </div>
            <span class="font-bold text-lg tracking-tight">ManagerAgent</span>
        </div>
        <div class="flex items-center gap-4">
            <!-- Theme Toggle -->
            <button @click="toggleDarkMode()" class="text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200">
                <template x-if="!darkMode">
                    <!-- Sun icon -->
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 9H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M16.243 17.657l.707-.707M6.343 6.343l.707-.707M14 12a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </template>
                <template x-if="darkMode">
                    <!-- Moon icon -->
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                </template>
            </button>
            
            <!-- Mobile Menu Toggle -->
            <button @click="mobileSidebarOpen = !mobileSidebarOpen" class="text-slate-600 dark:text-slate-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
        </div>
    </header>

    <!-- Sidebar Container -->
    <aside :class="mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'" 
           class="fixed inset-y-0 left-0 z-50 flex flex-col w-64 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 transition-transform duration-300 md:static md:translate-x-0 shrink-0 h-screen">
        
        <!-- Sidebar Brand Header -->
        <div class="flex items-center gap-3 px-6 py-5 border-b border-slate-200 dark:border-slate-800">
            <div class="w-9 h-9 rounded-xl bg-skyAccent dark:bg-navyAccent flex items-center justify-center text-white font-bold text-xl shadow-sm">
                {{ auth()->check() ? strtoupper(substr(auth()->user()->name, 0, 1)) : 'M' }}
            </div>
            <div>
                <span class="font-bold tracking-tight text-slate-900 dark:text-white">{{ auth()->check() ? auth()->user()->name : 'Manager Agent' }}</span>
                <span class="block text-xs text-slate-500 dark:text-slate-400">{{ auth()->check() ? ucfirst(session('active_role', auth()->user()->role)) . ' Panel' : 'Dashboard Panel' }}</span>
            </div>
        </div>

        <!-- Sidebar Navigation Links -->
        <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
            @if(auth()->check())
                @if(session('active_role') === 'admin')
                    <!-- Admin Navigation -->
                    <a href="{{ route('admin.index') }}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors {{ request()->routeIs('admin.*') ? 'bg-sky-50 dark:bg-blue-950/30 text-skyAccent dark:text-blue-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        Manage Users
                    </a>
                @elseif(in_array(session('active_role'), ['manager', 'team_lead']))
                    <!-- Manager/Team Lead Navigation -->
                    <a href="{{ route('dashboard.index') }}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors {{ request()->routeIs('dashboard.index') ? 'bg-sky-50 dark:bg-blue-950/30 text-skyAccent dark:text-blue-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        Dashboard
                    </a>

                    <a href="{{ route('dashboard.employees.index') }}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors {{ (request()->routeIs('dashboard.employees.*') && !request()->has('monitor')) ? 'bg-sky-50 dark:bg-blue-950/30 text-skyAccent dark:text-blue-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        Employees
                    </a>

                    <a href="{{ route('dashboard.teams.index') }}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors {{ request()->routeIs('dashboard.teams.*') ? 'bg-sky-50 dark:bg-blue-950/30 text-skyAccent dark:text-blue-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        Teams
                    </a>

                    <a href="{{ route('dashboard.projects.index') }}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors {{ request()->routeIs('dashboard.projects.*') ? 'bg-sky-50 dark:bg-blue-950/30 text-skyAccent dark:text-blue-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
                        Projects
                    </a>

                    <a href="{{ route('dashboard.engineering.index') }}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors {{ request()->routeIs('dashboard.engineering.*') ? 'bg-sky-50 dark:bg-blue-950/30 text-skyAccent dark:text-blue-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                        </svg>
                        Engineering
                    </a>

                    <a href="{{ route('dashboard.employees.index', ['monitor' => 'true']) }}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors {{ (request()->routeIs('dashboard.employees.*') && request()->has('monitor')) ? 'bg-sky-50 dark:bg-blue-950/30 text-skyAccent dark:text-blue-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        Live Activity
                    </a>

                    <a href="{{ route('dashboard.tasks.index') }}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors {{ request()->routeIs('dashboard.tasks.index') ? 'bg-sky-50 dark:bg-blue-950/30 text-skyAccent dark:text-blue-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                        Task
                    </a>

                    <a href="{{ route('dashboard.reports.index') }}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors {{ request()->routeIs('dashboard.reports.index') ? 'bg-sky-50 dark:bg-blue-950/30 text-skyAccent dark:text-blue-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364.364l-.707.707M21 12h-1M4 9H3m15.364 6.364l-.707-.707M6.343 6.343l.707-.707m9.9 5.05a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        AI Insights & Reports
                    </a>

                    <a href="{{ route('dashboard.leaderboard.index') }}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors {{ request()->routeIs('dashboard.leaderboard.index') ? 'bg-sky-50 dark:bg-blue-950/30 text-skyAccent dark:text-blue-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M7.21 15 2.66 7.14a2 2 0 0 1 .13-2.2L4.4 2.8A2 2 0 0 1 6 2h12a2 2 0 0 1 1.6.8l1.6 2.14a2 2 0 0 1 .14 2.2L16.79 15" />
                            <path d="M11 12 5.12 2.2" />
                            <path d="m13 12 5.88-9.8" />
                            <path d="M8 7h8" />
                            <circle cx="12" cy="17" r="5" />
                            <path d="M12 18v-2h-.5" />
                        </svg>
                        Leaderboards
                    </a>

                    <a href="{{ route('dashboard.attendance.index') }}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors {{ request()->routeIs('dashboard.attendance.*') ? 'bg-sky-50 dark:bg-blue-950/30 text-skyAccent dark:text-blue-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        Attendance
                    </a>

                    <!-- PREDICTIVE NAVIGATION SECTIONS -->
                    <div class="pt-4 border-t border-slate-150 dark:border-slate-800 space-y-1">
                        <span class="block px-4 pb-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Predictive Intelligence</span>

                        <a href="{{ route('dashboard.risks.index') }}" 
                           class="flex items-center justify-between px-4 py-3 rounded-xl font-medium transition-colors {{ request()->routeIs('dashboard.risks.index') ? 'bg-sky-50 dark:bg-blue-950/30 text-skyAccent dark:text-blue-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                Risk Center
                            </div>
                            @if($activeRisksCount > 0)
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                                </span>
                            @endif
                        </a>

                        <a href="{{ route('dashboard.health.index') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors {{ request()->routeIs('dashboard.health.index') ? 'bg-sky-50 dark:bg-blue-950/30 text-skyAccent dark:text-blue-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                            Team Health
                        </a>

                        <a href="{{ route('dashboard.workload.index') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors {{ request()->routeIs('dashboard.workload.index') ? 'bg-sky-50 dark:bg-blue-950/30 text-skyAccent dark:text-blue-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path></svg>
                            Workload Analysis
                        </a>

                        <a href="{{ route('dashboard.notifications.index') }}" 
                           class="flex items-center justify-between px-4 py-3 rounded-xl font-medium transition-colors {{ request()->routeIs('dashboard.notifications.index') ? 'bg-sky-50 dark:bg-blue-950/30 text-skyAccent dark:text-blue-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                                Notifications
                            </div>
                            @if($unreadNotificationsCount > 0)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-500 text-white leading-none">
                                    {{ $unreadNotificationsCount }}
                                </span>
                            @endif
                        </a>

                        <a href="{{ route('dashboard.developer.index') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors {{ request()->routeIs('dashboard.developer.*') ? 'bg-sky-50 dark:bg-blue-950/30 text-skyAccent dark:text-blue-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Developer Tools
                        </a>
                    </div>
                @elseif(session('active_role') === 'employee')
                    <!-- Employee Navigation -->
                    <a href="{{ route('employee.dashboard') }}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors {{ request()->routeIs('employee.dashboard') ? 'bg-sky-50 dark:bg-blue-950/30 text-skyAccent dark:text-blue-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z"></path></svg>
                        My Portal
                    </a>

                    <a href="{{ route('employee.attendance.index') }}" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors {{ request()->routeIs('employee.attendance.*') ? 'bg-sky-50 dark:bg-blue-950/30 text-skyAccent dark:text-blue-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        Attendance
                    </a>
                @endif

                <!-- Settings (Shared) -->
                <a href="{{ route('settings.index') }}" 
                   class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-colors {{ request()->routeIs('settings.*') ? 'bg-sky-50 dark:bg-blue-950/30 text-skyAccent dark:text-blue-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Settings
                </a>

                @if(in_array(auth()->user()->role, ['manager', 'team_lead']))
                    <!-- Role Switcher Form -->
                    <div class="pt-4 border-t border-slate-150 dark:border-slate-800">
                        <form action="{{ route('role.switch') }}" method="POST">
                            @csrf
                            <button type="submit" 
                                    class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-skyAccent dark:text-blue-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                <svg class="w-4 h-4 text-skyAccent dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                                Switch to {{ session('active_role') === 'employee' ? (auth()->user()->role === 'team_lead' ? 'Team Lead View' : 'Manager View') : 'Employee View' }}
                            </button>
                        </form>
                    </div>
                @endif


            @endif
        </nav>

        <!-- Sidebar Footer Action (Log Out) -->
        <div class="px-6 py-5 border-t border-slate-200 dark:border-slate-800">
            @if(auth()->check())
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold text-red-500 hover:bg-red-50 dark:hover:bg-red-950/20 transition-colors">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        Log Out
                    </button>
                </form>
            @endif
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto h-screen relative">
        <!-- Top Navbar -->
        <header class="hidden md:flex items-center justify-between px-8 py-5 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 shrink-0 sticky top-0 z-40">
            <h1 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white shrink-0">
                @if(auth()->check())
                    @if(session('active_role') === 'admin')
                        System Admin Panel
                    @elseif(session('active_role') === 'team_lead')
                        Team Lead Workspace
                    @elseif(session('active_role') === 'manager')
                        Manager Workspace
                    @else
                        Employee Workspace
                    @endif
                @else
                    Workspace
                @endif
            </h1>
            
            @if(auth()->check())
                <div class="flex items-center gap-6">
                    <!-- Notifications Bell Dropdown -->
                    @if (in_array(session('active_role'), ['manager', 'team_lead']))
                        <div class="relative animate-fade-in" x-data="{ open: false }">
                            <button @click="open = !open" class="relative p-2 text-slate-400 hover:text-slate-650 dark:hover:text-slate-350 focus:outline-none transition-colors">
                                <!-- Bell icon -->
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                </svg>
                                @if($unreadNotificationsCount > 0)
                                    <span class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white leading-none">
                                        {{ $unreadNotificationsCount }}
                                    </span>
                                @endif
                            </button>
                            
                            <!-- Dropdown list -->
                            <div x-cloak x-show="open" @click.outside="open = false" 
                                 class="absolute right-0 mt-2 w-80 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xl py-2 z-50 overflow-hidden">
                                <div class="px-4 py-2 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                                    <span class="font-bold text-sm text-slate-800 dark:text-slate-200">Recent Alerts</span>
                                    @if($unreadNotificationsCount > 0)
                                        <form action="{{ route('dashboard.notifications.read-all') }}" method="POST">
                                            @csrf
                                            <button type="submit" class="text-[10px] font-bold text-skyAccent hover:text-sky-700 dark:text-blue-400 dark:hover:text-blue-300">Mark all read</button>
                                        </form>
                                    @endif
                                </div>
                                
                                <div class="max-h-64 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/60">
                                    @forelse($recentNotifications as $notif)
                                        <a href="{{ route('dashboard.notifications.index') }}" class="block px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                            <div class="flex items-start gap-2">
                                                <span class="w-2 h-2 rounded-full mt-1.5 shrink-0 
                                                    @if($notif->severity === 'CRITICAL') bg-red-500 
                                                    @elseif($notif->severity === 'WARNING') bg-amber-500 
                                                    @else bg-blue-500 @endif"></span>
                                                <div class="text-left">
                                                    <span class="block text-xs font-semibold text-slate-850 dark:text-slate-200">{{ $notif->title }}</span>
                                                    <span class="block text-[10px] text-slate-400 mt-0.5">{{ $notif->created_at->diffForHumans() }}</span>
                                                </div>
                                            </div>
                                        </a>
                                    @empty
                                        <div class="px-4 py-6 text-center text-xs text-slate-400">
                                            No recent notifications.
                                        </div>
                                    @endforelse
                                </div>
                                
                                <div class="border-t border-slate-100 dark:border-slate-800 text-center py-2 bg-slate-50 dark:bg-slate-900/50">
                                    <a href="{{ route('dashboard.notifications.index') }}" class="text-xs font-bold text-slate-650 hover:text-skyAccent dark:text-slate-400 dark:hover:text-blue-400">View all notifications</a>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Profile Info -->
                    <div class="flex items-center gap-4">
                        <div class="text-right">
                            <span class="block text-sm font-semibold text-slate-800 dark:text-slate-200">{{ auth()->user()->name }}</span>
                            <span class="block text-xs text-slate-500 dark:text-slate-400">{{ ucfirst(session('active_role', auth()->user()->role)) }}</span>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-sky-150 dark:bg-slate-800 text-skyAccent dark:text-blue-450 flex items-center justify-center font-bold text-slate-600 dark:text-slate-350">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>
                    </div>
                </div>
            @endif
        </header>

        <!-- Main Body Inner Content -->
        <div class="p-6 md:p-8 flex-1">
            <!-- Flash Alert Container -->
            @if (session('success'))
                <div class="mb-6 p-4 rounded-xl bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div x-cloak 
         x-show="mobileSidebarOpen" 
         @click="mobileSidebarOpen = false" 
         class="fixed inset-0 z-40 bg-slate-950/40 md:hidden transition-opacity"></div>

    @if(auth()->check())
        <!-- AI Manager Agent Floating Chat Widget -->
        <div x-data="aiAgentWidget()" 
             x-init="initWidget()"
             class="fixed bottom-6 right-6 z-50 flex flex-col items-end">
            
            <!-- A. Floating Trigger Button -->
            <button @click="toggleOpen()"
                    class="w-14 h-14 bg-slate-900 hover:bg-slate-850 text-white rounded-full flex items-center justify-center shadow-2xl hover:scale-105 active:scale-95 focus:outline-none relative border border-slate-700"
                    title="AI Manager Agent">
                <!-- Normal Chat Icon (shown when closed) -->
                <svg x-show="!isOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                </svg>
                <!-- Close Icon (shown when open) -->
                <svg x-show="isOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                <!-- Notification Badge dot -->
                <span class="absolute top-0 right-0 w-3.5 h-3.5 rounded-full bg-emerald-500 border-2 border-white dark:border-slate-900 animate-pulse"></span>
            </button>

            <!-- B. Floating Widget Window Chat box -->
            <div x-cloak
                 x-show="isOpen"
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 translate-y-8 scale-90"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-8 scale-90"
                 class="absolute bottom-16 right-0 w-[420px] max-w-[calc(100vw-2rem)] h-[600px] max-h-[calc(100vh-8rem)] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl flex flex-col overflow-hidden text-sm">
                
                <!-- 1. HEADER -->
                <header class="bg-slate-900 border-b border-slate-800 text-white p-4 flex items-center justify-between shadow-sm shrink-0">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></div>
                        <div class="text-left">
                            <span class="block font-bold text-xs">AI Manager Agent</span>
                            <span class="block text-[8px] uppercase tracking-wider text-slate-400 font-semibold">Telemetry active</span>
                        </div>
                    </div>

                    <!-- Action controls -->
                    <div class="flex items-center gap-2.5">
                        <!-- Date Filter button -->
                        <button @click="showFilters = !showFilters" 
                                class="p-1 hover:bg-slate-800 rounded transition-colors text-slate-400 hover:text-white"
                                :class="startDate || endDate ? 'text-emerald-400' : ''"
                                title="Filter date range">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </button>

                        <!-- Export button -->
                        <button x-show="activeConversationId && widgetView === 'chat'" 
                                @click="exportActiveChat()" 
                                class="p-1 text-slate-400 hover:text-white hover:bg-slate-800 rounded transition-colors"
                                title="Export logs to JSON">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                        </button>

                        <!-- Toggle history view -->
                        <button @click="toggleView('history')" 
                                class="p-1 rounded transition-colors"
                                :class="widgetView === 'history' ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800'"
                                title="Chat History logs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </button>

                        <!-- Toggle telemetry insights -->
                        <button @click="toggleView('insights')" 
                                class="p-1 rounded transition-colors"
                                :class="widgetView === 'insights' ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800'"
                                title="Consulted sources & metrics">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </button>
                    </div>
                </header>

                <!-- 2. DATE FILTERS SLIDER -->
                <div x-cloak
                     x-show="showFilters"
                     x-transition
                     class="bg-slate-50 dark:bg-slate-950 border-b border-slate-205 p-3 flex items-center justify-between gap-3 text-[10px] font-semibold shrink-0">
                    <div class="flex items-center gap-2">
                        <span class="text-slate-400">From:</span>
                        <input type="date" x-model="startDate" class="bg-white dark:bg-slate-900 border border-slate-205 rounded px-1.5 py-0.5 text-[10px] text-slate-700 dark:text-slate-250">
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-slate-400">To:</span>
                        <input type="date" x-model="endDate" class="bg-white dark:bg-slate-900 border border-slate-205 rounded px-1.5 py-0.5 text-[10px] text-slate-700 dark:text-slate-250">
                    </div>
                    <button @click="clearFilters()" class="text-red-500 hover:text-red-650">Reset</button>
                </div>

                <!-- 3. MAIN CONTENT CONTAINER -->
                <div class="flex-1 overflow-hidden relative bg-slate-50/50 dark:bg-slate-950/20">
                    
                    <!-- Tab A: Active Chat View -->
                    <div x-show="widgetView === 'chat'" class="h-full flex flex-col">
                        <!-- Messages scroll body -->
                        <div class="flex-1 overflow-y-auto p-4 space-y-4 widget-scroll-container" id="widget-chat-scroller">
                            <template x-for="(msg, index) in messages" :key="index">
                                <div class="flex flex-col" :class="msg.role === 'user' ? 'items-end' : 'items-start'">
                                    <div class="flex items-center gap-1 mb-0.5 text-[9px] text-slate-400 font-bold px-1">
                                        <span x-text="msg.role === 'user' ? 'Manager (You)' : 'AI Agent'"></span>
                                        <span>&bull;</span>
                                        <span x-text="formatTime(msg.created_at)"></span>
                                    </div>
                                    <div class="max-w-[90%] rounded-2xl p-3 shadow-sm leading-relaxed text-xs"
                                         :class="msg.role === 'user' 
                                             ? 'bg-indigo-600 text-white rounded-tr-none' 
                                             : 'bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 text-slate-800 dark:text-slate-200 rounded-tl-none'">
                                        
                                        <!-- Content text -->
                                        <div class="whitespace-pre-line font-medium" x-text="msg.content"></div>

                                        <!-- Loading status -->
                                        <template x-if="msg.isStreaming">
                                            <div class="flex items-center gap-1 mt-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce"></span>
                                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce [animation-delay:0.2s]"></span>
                                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce [animation-delay:0.4s]"></span>
                                            </div>
                                        </template>

                                        <!-- Telemetry Structured Visuals (Chart/KPI/Table) -->
                                        <template x-if="!msg.isStreaming && msg.role === 'assistant' && msg.structured_response">
                                            <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-850 space-y-3">
                                                <!-- Visual Grid -->
                                                <template x-if="msg.structured_response.visual_type && msg.structured_response.visual_type !== 'null'">
                                                    <div class="bg-slate-50 dark:bg-slate-950 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800">
                                                        
                                                        <!-- KPI Display -->
                                                        <template x-if="msg.structured_response.visual_type === 'kpi'">
                                                            <div class="grid grid-cols-2 gap-2">
                                                                <template x-for="row in msg.structured_response.visual_data.rows">
                                                                    <div class="bg-white dark:bg-slate-900 px-2 py-1.5 rounded-lg border border-slate-100 dark:border-slate-800/80 text-center">
                                                                        <span class="block text-[8px] font-bold text-slate-400 uppercase truncate" x-text="row[0]"></span>
                                                                        <span class="block text-sm font-extrabold text-indigo-600 dark:text-blue-400" x-text="row[1]"></span>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </template>

                                                        <!-- Table Display -->
                                                        <template x-if="msg.structured_response.visual_type === 'table' || msg.structured_response.visual_type === 'leaderboard'">
                                                            <div class="space-y-2">
                                                                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-1.5">
                                                                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider" x-text="msg.structured_response.visual_type === 'leaderboard' ? 'Leaderboard' : 'Data Table'"></span>
                                                                    <button @click="exportTableToCSV(msg)" 
                                                                            class="text-[9px] font-bold text-skyAccent hover:text-sky-600 dark:text-skyAccent dark:hover:text-sky-400 flex items-center gap-1 transition-colors"
                                                                            title="Download Table as CSV">
                                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                                                        </svg>
                                                                        CSV
                                                                    </button>
                                                                </div>
                                                                <div class="overflow-x-auto">
                                                                    <table class="w-full text-left text-[10px]">
                                                                        <thead>
                                                                            <tr class="border-b border-slate-200 dark:border-slate-850">
                                                                                <template x-for="header in msg.structured_response.visual_data.headers">
                                                                                    <th class="pb-1 font-bold text-slate-400 uppercase" x-text="header"></th>
                                                                                </template>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody class="divide-y divide-slate-100 dark:divide-slate-850">
                                                                            <template x-for="row in msg.structured_response.visual_data.rows">
                                                                                <tr>
                                                                                    <template x-for="cell in row">
                                                                                        <td class="py-1.5 text-slate-700 dark:text-slate-350 truncate max-w-[80px]" x-text="cell"></td>
                                                                                    </template>
                                                                                </tr>
                                                                            </template>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </template>

                                                        <!-- Chart canvas -->
                                                        <template x-if="msg.structured_response.visual_type === 'chart'">
                                                            <div class="relative h-32 w-full">
                                                                <canvas :id="'widget-chart-' + msg.id"></canvas>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </template>

                                                <!-- AI Analysis alert -->
                                                <template x-if="msg.structured_response.ai_analysis">
                                                    <div class="p-2 bg-amber-50/50 dark:bg-amber-950/10 border border-amber-200/40 dark:border-amber-900/20 rounded-xl text-[10px] leading-relaxed text-slate-650 dark:text-slate-350">
                                                        <span class="font-bold text-amber-800 dark:text-amber-300 block mb-0.5">&bull; Telemetry Insight</span>
                                                        <p x-text="msg.structured_response.ai_analysis"></p>
                                                    </div>
                                                </template>

                                                <!-- Metrics pills -->
                                                <template x-if="msg.structured_response.supporting_metrics && msg.structured_response.supporting_metrics.length > 0">
                                                    <div class="flex flex-wrap gap-1">
                                                        <template x-for="m in msg.structured_response.supporting_metrics">
                                                            <span class="inline-flex px-1.5 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950/20 text-indigo-600 dark:text-blue-400 text-[9px] font-bold" x-text="m"></span>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <!-- Empty welcome view with questions -->
                            <div x-show="messages.length === 0" class="flex flex-col items-center justify-center text-center py-6 px-3 space-y-4">
                                <span class="text-3xl animate-bounce">🤖</span>
                                <div>
                                    <span class="block font-bold text-sm text-slate-900 dark:text-white">Hello, {{ auth()->user()->name }}! 👋</span>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">I am your AI Telemetry Assistant. How can I help you manage your workspace today?</p>
                                </div>
                                <div class="w-full space-y-2">
                                    <template x-for="q in suggestedQuestions" :key="q">
                                        <button @click="selectSuggested(q)" 
                                                class="w-full p-2.5 text-left text-[11px] font-semibold border border-slate-205 hover:border-skyAccent bg-white dark:bg-slate-900 rounded-xl hover:bg-slate-50 transition-colors truncate text-slate-700 dark:text-slate-300">
                                            <span x-text="q"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Bottom panel input -->
                        <footer class="p-3 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shrink-0">
                            <!-- Follow-up pills -->
                            <div x-show="messages.length > 0" class="flex items-center gap-1.5 overflow-x-auto pb-2 scrollbar-none whitespace-nowrap">
                                <template x-for="q in suggestedQuestions.slice(0, 3)" :key="q">
                                    <button @click="selectSuggested(q)" 
                                            class="px-2 py-0.5 text-[9px] font-bold border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-500 rounded hover:border-skyAccent transition-colors">
                                        <span x-text="q"></span>
                                    </button>
                                </template>
                            </div>
                            <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-800 px-3 py-2 rounded-xl focus-within:border-skyAccent transition-colors">
                                <input type="text" 
                                       x-model="question" 
                                       @keydown.enter.prevent="submitMessage()"
                                       placeholder="Ask AI Agent..." 
                                       class="flex-1 bg-transparent border-none text-xs text-slate-800 dark:text-slate-200 focus:outline-none placeholder:text-slate-400">
                                <button @click="submitMessage()" 
                                        class="p-1.5 bg-gradient-to-r from-skyAccent to-indigo-800 text-white rounded-lg hover:from-sky-500 hover:to-indigo-900 transition-all shrink-0 flex items-center justify-center"
                                        :class="loading || !question.trim() ? 'opacity-50 cursor-not-allowed' : ''"
                                        :disabled="loading || !question.trim()">
                                    <svg class="w-3 h-3 transform rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                    </svg>
                                </button>
                            </div>
                        </footer>
                    </div>

                    <!-- Tab B: History Logs View -->
                    <div x-show="widgetView === 'history'" class="h-full flex flex-col p-4">
                        <div class="relative mb-3 shrink-0">
                            <input type="text" 
                                   x-model="historySearch" 
                                   placeholder="Search past conversations..." 
                                   class="w-full pl-8 pr-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:border-indigo-500">
                            <svg class="absolute left-2.5 top-2.5 w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>

                        <div class="flex-1 overflow-y-auto space-y-1 widget-scroll-container">
                            <template x-for="item in filteredConversations()" :key="item.id">
                                <div class="group flex items-center justify-between p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                                     :class="activeConversationId == item.id ? 'bg-sky-50/50 dark:bg-blue-950/20 text-skyAccent dark:text-blue-450 font-bold' : 'text-slate-650 dark:text-slate-455'"
                                     @click="selectConversation(item.id)">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                                        <span class="text-xs truncate" x-text="item.title"></span>
                                    </div>
                                    <button @click.stop="deleteConversation(item.id)" class="p-1 hover:text-red-500 rounded opacity-0 group-hover:opacity-100 transition-opacity">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    </button>
                                </div>
                            </template>
                            <div x-show="filteredConversations().length === 0" class="p-4 text-center text-xs text-slate-400 italic">No history matching search.</div>
                        </div>

                        <div class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-800 shrink-0 flex gap-2">
                            <button @click="startNewConversation()" class="flex-1 py-2 bg-gradient-to-r from-skyAccent to-indigo-800 hover:from-sky-500 hover:to-indigo-900 text-white rounded-xl text-xs font-bold text-center transition-all">Start New Conversation</button>
                            <button x-show="activeConversationId" @click="clearActiveConversation()" class="px-3 py-2 text-xs font-bold text-red-500 border border-red-200 dark:border-red-900 rounded-xl hover:bg-red-50 dark:hover:bg-red-950/20 transition-colors">Clear Chat</button>
                        </div>
                    </div>

                    <!-- Tab C: Telemetry Insights Panel View -->
                    <div x-show="widgetView === 'insights'" class="h-full flex flex-col p-4 overflow-y-auto space-y-4 widget-scroll-container">
                        <span class="block text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Scanned Telemetry Tables</span>
                        <div class="space-y-2">
                            <template x-if="lastAssistantMessage()">
                                <template x-for="src in lastAssistantMessage().data_sources">
                                    <div class="flex items-center gap-2 text-xs px-3 py-2 bg-sky-50/50 dark:bg-blue-950/20 text-skyAccent dark:text-blue-400 rounded-xl border border-sky-100/30 dark:border-slate-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-skyAccent"></span>
                                        <span x-text="src"></span>
                                    </div>
                                </template>
                            </template>
                            <template x-if="!lastAssistantMessage()">
                                <div class="space-y-1.5 text-xs text-slate-500 font-semibold pl-1">
                                    <div><span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500 mr-2"></span> employees database</div>
                                    <div><span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500 mr-2"></span> project details</div>
                                    <div><span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500 mr-2"></span> task parameters</div>
                                    <div><span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500 mr-2"></span> attendance check-ins</div>
                                    <div><span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500 mr-2"></span> gitlab code contributions</div>
                                    <div><span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500 mr-2"></span> team health logs</div>
                                </div>
                            </template>
                        </div>

                        <span class="block text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider border-t border-slate-100 dark:border-slate-850 pt-3">AI Context State</span>
                        <div class="p-3 bg-slate-50 dark:bg-slate-950 rounded-xl border border-slate-150 dark:border-slate-800 text-[11px] font-semibold space-y-2">
                            <div class="flex justify-between">
                                <span class="text-slate-400">Memory Scope</span>
                                <span class="text-indigo-600 dark:text-blue-400 uppercase" x-text="'{{ session('active_role', auth()->user()->role) }}'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Total Conversations</span>
                                <span class="text-slate-750 dark:text-slate-300" x-text="conversations.length"></span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Alpine Widget Controller Script -->
        <script>
        function aiAgentWidget() {
            return {
                isOpen: false,
                widgetView: 'chat', // chat, history, insights
                activeConversationId: {{ $activeConversation ? $activeConversation->id : 'null' }},
                activeConversationTitle: '{{ $activeConversation ? $activeConversation->title : '' }}',
                historySearch: '',
                startDate: '{{ request('start_date') }}' || new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
                endDate: '{{ request('end_date') }}' || new Date().toISOString().split('T')[0],
                question: '',
                messages: {!! $widgetMessagesJson !!},
                conversations: {!! $widgetConversationsJson !!},
                suggestedQuestions: {!! $widgetSuggestedQuestionsJson !!},
                loading: false,
                showFilters: false,

                initWidget() {
                    this.scrollToBottom();
                    this.$nextTick(() => {
                        this.renderCharts();
                    });
                },

                toggleOpen() {
                    this.isOpen = !this.isOpen;
                    if (this.isOpen) {
                        this.scrollToBottom();
                        this.$nextTick(() => {
                            this.renderCharts();
                        });
                    }
                },

                toggleView(view) {
                    this.widgetView = (this.widgetView === view) ? 'chat' : view;
                    if (this.widgetView === 'chat') {
                        this.scrollToBottom();
                        this.$nextTick(() => {
                            this.renderCharts();
                        });
                    }
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

                async selectConversation(id) {
                    this.loading = true;
                    this.widgetView = 'chat';
                    try {
                        const response = await fetch(`/ai-agent/export/${id}`);
                        const data = await response.json();
                        this.activeConversationId = data.conversation_id;
                        this.activeConversationTitle = data.title;
                        this.messages = data.messages.map(m => ({
                            id: m.id || Math.random(),
                            role: m.role,
                            content: m.content,
                            data_sources: m.data_sources || [],
                            structured_response: m.structured,
                            created_at: m.timestamp,
                            isStreaming: false
                        }));
                        this.scrollToBottom();
                        this.$nextTick(() => {
                            this.renderCharts();
                        });
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.loading = false;
                    }
                },

                startNewConversation() {
                    this.activeConversationId = null;
                    this.activeConversationTitle = 'New Chat';
                    this.messages = [];
                    this.widgetView = 'chat';
                },

                async deleteConversation(id) {
                    if (confirm('Delete this entire conversation?')) {
                        try {
                            await fetch(`/ai-agent/delete/${id}`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({ _method: 'DELETE' })
                            });
                            this.conversations = this.conversations.filter(c => c.id !== id);
                            if (this.activeConversationId === id) {
                                this.startNewConversation();
                            }
                        } catch (e) {
                            console.error(e);
                        }
                    }
                },

                async clearActiveConversation() {
                    if (confirm('Clear current messages?')) {
                        try {
                            await fetch(`/ai-agent/clear/${this.activeConversationId}`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                    'Accept': 'application/json'
                                }
                            });
                            this.messages = [];
                            this.widgetView = 'chat';
                        } catch (e) {
                            console.error(e);
                        }
                    }
                },

                exportTableToCSV(msg) {
                    if (msg && msg.structured_response && msg.structured_response.visual_data && msg.structured_response.visual_data.rows) {
                        const visualData = msg.structured_response.visual_data;
                        const headers = visualData.headers || [];
                        const rows = visualData.rows || [];
                        
                        let csvContent = "";
                        
                        let durationLabel = "Weekly Report";
                        let start = this.startDate;
                        let end = this.endDate;
                        
                        const defaultStart = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
                        const defaultEnd = new Date().toISOString().split('T')[0];
                        
                        if (!start || !end) {
                            start = defaultStart;
                            end = defaultEnd;
                            durationLabel = "Weekly Report";
                        } else if (start === defaultStart && end === defaultEnd) {
                            durationLabel = "Weekly Report";
                        } else {
                            const sDate = new Date(start);
                            const eDate = new Date(end);
                            const diffTime = Math.abs(eDate - sDate);
                            const diffDays = Math.round(diffTime / (1000 * 60 * 60 * 24));
                            if (diffDays === 7) {
                                durationLabel = "Weekly Report";
                            } else {
                                durationLabel = `${diffDays}-Day Report`;
                            }
                        }
                        
                        csvContent += `"Report Duration","${durationLabel} (From ${start} To ${end})"\n\n`;
                        
                        if (headers.length > 0) {
                            csvContent += headers.map(h => `"${h.replace(/"/g, '""')}"`).join(",") + "\n";
                        }
                        
                        rows.forEach(row => {
                            csvContent += row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(",") + "\n";
                        });
                        
                        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                        const link = document.createElement("a");
                        const url = URL.createObjectURL(blob);
                        link.setAttribute("href", url);
                        link.setAttribute("download", `telemetry-report-${msg.id || 'export'}.csv`);
                        link.style.visibility = 'hidden';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    }
                },

                exportActiveChat() {
                    // Find the last assistant message that contains a table structured response
                    let tableMsg = null;
                    for (let i = this.messages.length - 1; i >= 0; i--) {
                        const m = this.messages[i];
                        if (m.role === 'assistant' && m.structured_response && m.structured_response.visual_data && m.structured_response.visual_data.rows) {
                            tableMsg = m;
                            break;
                        }
                    }

                    if (tableMsg) {
                        this.exportTableToCSV(tableMsg);
                    } else if (this.activeConversationId) {
                        // Fallback to JSON export if no table is present
                        window.location.href = `/ai-agent/export/${this.activeConversationId}`;
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
                        const chatBody = document.getElementById('widget-chat-scroller');
                        if (chatBody) {
                            chatBody.scrollTop = chatBody.scrollHeight;
                        }
                    });
                },

                async submitMessage() {
                    if (!this.question.trim() || this.loading) return;

                    const userMsg = this.question;
                    this.question = '';
                    
                    this.messages.push({
                        role: 'user',
                        content: userMsg,
                        created_at: new Date().toISOString(),
                        isStreaming: false
                    });

                    // Add loading placeholder for assistant immediately to show three bouncing dots
                    const streamIndex = this.messages.push({
                        role: 'assistant',
                        content: '',
                        isStreaming: true,
                        data_sources: [],
                        structured_response: null,
                        created_at: new Date().toISOString()
                    }) - 1;

                    this.loading = true;
                    this.scrollToBottom();

                    try {
                        const response = await fetch('/ai-agent/message', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                question: userMsg,
                                conversation_id: this.activeConversationId,
                                start_date: this.startDate,
                                end_date: this.endDate
                            })
                        });

                        if (!response.ok) {
                            throw new Error('Server error');
                        }

                        const data = await response.json();
                        this.loading = false;

                        if (data.success) {
                            const isNewConversation = !this.activeConversationId;
                            this.activeConversationId = data.conversation_id;
                            this.activeConversationTitle = data.conversation_title;

                            const fullMsg = data.message;
                            const directAnswer = fullMsg.content;
                            
                            // Update placeholder with correct data
                            this.messages[streamIndex].id = fullMsg.id;
                            this.messages[streamIndex].created_at = fullMsg.created_at;

                            this.scrollToBottom();

                            // Simulated streaming
                            let currentLength = 0;
                            const typingSpeed = 8;
                            const interval = setInterval(() => {
                                if (currentLength < directAnswer.length) {
                                    currentLength += Math.min(3, directAnswer.length - currentLength);
                                    this.messages[streamIndex].content = directAnswer.slice(0, currentLength);
                                    this.scrollToBottom();
                                } else {
                                    clearInterval(interval);
                                    
                                    this.messages[streamIndex].content = directAnswer;
                                    this.messages[streamIndex].isStreaming = false;
                                    this.messages[streamIndex].data_sources = fullMsg.data_sources;
                                    this.messages[streamIndex].structured_response = fullMsg.structured_response;
                                    
                                    if (fullMsg.structured_response && fullMsg.structured_response.visual_type === 'chart') {
                                        this.renderCharts();
                                    }
                                    
                                    this.scrollToBottom();

                                    if (isNewConversation) {
                                        // Add to history list dynamically on client side
                                        this.conversations.unshift({
                                            id: data.conversation_id,
                                            title: data.conversation_title,
                                            updated_at: new Date().toISOString()
                                        });
                                    }
                                }
                            }, typingSpeed);

                        } else {
                            this.messages[streamIndex].content = 'Telemetry processing failed. Request parameters out of range.';
                            this.messages[streamIndex].isStreaming = false;
                            this.scrollToBottom();
                        }

                    } catch (error) {
                        console.error(error);
                        this.loading = false;
                        this.messages[streamIndex].content = 'Telemetry server error. Fallback connection offline.';
                        this.messages[streamIndex].isStreaming = false;
                        this.scrollToBottom();
                    }
                },

                renderCharts() {
                    this.messages.forEach(msg => {
                        if (msg.structured_response && msg.structured_response.visual_type === 'chart') {
                            const chartData = msg.structured_response.visual_data;
                            if (chartData && chartData.chart_type && chartData.chart_labels) {
                                const canvasId = 'widget-chart-' + msg.id;
                                this.$nextTick(() => {
                                    const canvas = document.getElementById(canvasId);
                                    if (canvas) {
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
                                                label: 'Metrics telemetry',
                                                data: chartData.chart_values,
                                                backgroundColor: '#0ea5e9',
                                                borderRadius: 4,
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
                                                            boxWidth: 8,
                                                            font: { size: 8 }
                                                        }
                                                    }
                                                },
                                                scales: chartData.chart_type === 'pie' ? {} : {
                                                    y: {
                                                        beginAtZero: true,
                                                        grid: { color: gridColor },
                                                        ticks: { color: textColor, font: { size: 7 } }
                                                    },
                                                    x: {
                                                        grid: { display: false },
                                                        ticks: { color: textColor, font: { size: 7 } }
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
        /* Widget specific styles */
        .widget-scroll-container::-webkit-scrollbar {
            width: 4px;
        }
        .widget-scroll-container::-webkit-scrollbar-track {
            background: transparent;
        }
        .widget-scroll-container::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }
        .dark .widget-scroll-container::-webkit-scrollbar-thumb {
            background: #334155;
        }
        </style>
    @endif
</body>
</html>
