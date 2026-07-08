<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - {{ config('app.name', 'ManagerAgent') }}</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        skyAccent: '#0ea5e9',
                        navyAccent: '#1e3a8a',
                    }
                }
            }
        }
    </script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 min-h-screen flex items-center justify-center p-4 relative overflow-hidden"
      x-data="{ 
          darkMode: localStorage.getItem('darkMode') === 'true',
          selectedRole: 'manager',
          init() {
              if (this.darkMode) {
                  document.documentElement.classList.add('dark');
              } else {
                  document.documentElement.classList.remove('dark');
              }
          }
      }">

    <!-- Background Decorative Gradients -->
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-skyAccent/20 rounded-full blur-3xl"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-indigo-500/20 rounded-full blur-3xl"></div>

    <!-- Login Container -->
    <div class="w-full max-w-lg bg-white/70 dark:bg-slate-900/60 backdrop-blur-xl border border-slate-200 dark:border-slate-800 p-8 rounded-3xl shadow-xl z-10 transition-all duration-300">
        
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex w-12 h-12 rounded-2xl bg-skyAccent dark:bg-navyAccent items-center justify-center text-white font-bold text-2xl shadow-md mb-3">
                M
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Welcome to ManagerAgent</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Please sign in with your credentials and select your workspace role</p>
        </div>

        <!-- Session Flash Statuses -->
        @if (session('error'))
            <div class="mb-5 p-4 rounded-2xl bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-400 text-sm flex items-center gap-2">
                <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Role Selector Cards -->
            <div class="space-y-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Select Role Portal</label>
                <input type="hidden" name="role" :value="selectedRole">
                
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <!-- Employee Card -->
                    <button type="button" 
                            @click="selectedRole = 'employee'"
                            :class="selectedRole === 'employee' ? 'border-skyAccent bg-sky-50/50 dark:bg-sky-950/20 text-skyAccent dark:text-sky-400 font-semibold shadow-sm' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 text-slate-600 dark:text-slate-400'"
                            class="flex flex-col items-center justify-center p-3 rounded-2xl border text-center transition-all duration-200 group">
                        <svg class="w-5 h-5 mb-1 text-slate-400 group-hover:text-skyAccent transition-colors" :class="selectedRole === 'employee' && 'text-skyAccent dark:text-sky-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        <span class="text-xs">Employee</span>
                    </button>

                    <!-- Team Lead Card -->
                    <button type="button" 
                            @click="selectedRole = 'team_lead'"
                            :class="selectedRole === 'team_lead' ? 'border-skyAccent bg-sky-50/50 dark:bg-sky-950/20 text-skyAccent dark:text-sky-400 font-semibold shadow-sm' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 text-slate-600 dark:text-slate-400'"
                            class="flex flex-col items-center justify-center p-3 rounded-2xl border text-center transition-all duration-200 group">
                        <svg class="w-5 h-5 mb-1 text-slate-400 group-hover:text-skyAccent transition-colors" :class="selectedRole === 'team_lead' && 'text-skyAccent dark:text-sky-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        <span class="text-xs">Team Lead</span>
                    </button>

                    <!-- Manager Card -->
                    <button type="button" 
                            @click="selectedRole = 'manager'"
                            :class="selectedRole === 'manager' ? 'border-skyAccent bg-sky-50/50 dark:bg-sky-950/20 text-skyAccent dark:text-sky-400 font-semibold shadow-sm' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 text-slate-600 dark:text-slate-400'"
                            class="flex flex-col items-center justify-center p-3 rounded-2xl border text-center transition-all duration-200 group">
                        <svg class="w-5 h-5 mb-1 text-slate-400 group-hover:text-skyAccent transition-colors" :class="selectedRole === 'manager' && 'text-skyAccent dark:text-sky-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span class="text-xs">Manager</span>
                    </button>

                    <!-- Admin Card -->
                    <button type="button" 
                            @click="selectedRole = 'admin'"
                            :class="selectedRole === 'admin' ? 'border-skyAccent bg-sky-50/50 dark:bg-sky-950/20 text-skyAccent dark:text-sky-400 font-semibold shadow-sm' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 text-slate-600 dark:text-slate-400'"
                            class="flex flex-col items-center justify-center p-3 rounded-2xl border text-center transition-all duration-200 group">
                        <svg class="w-5 h-5 mb-1 text-slate-400 group-hover:text-skyAccent transition-colors" :class="selectedRole === 'admin' && 'text-skyAccent dark:text-sky-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        <span class="text-xs">Admin</span>
                    </button>
                </div>
                @error('role')
                    <span class="text-xs text-red-500 font-semibold mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Email Field -->
            <div class="space-y-1">
                <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Email Address</label>
                <div class="relative">
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="yourname@company.com"
                           class="w-full px-4 py-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-800 rounded-2xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-sm transition-all">
                </div>
                @error('email')
                    <span class="text-xs text-red-500 font-semibold mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Password Field -->
            <div class="space-y-1">
                <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Password</label>
                <input type="password" id="password" name="password" required placeholder="••••••••"
                       class="w-full px-4 py-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-800 rounded-2xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-sm transition-all">
                @error('password')
                    <span class="text-xs text-red-500 font-semibold mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Submit Button -->
            <button type="submit" 
                    class="w-full py-3.5 bg-skyAccent hover:bg-sky-500 dark:bg-blue-600 dark:hover:bg-blue-500 text-white font-bold rounded-2xl shadow-md hover:shadow-lg active:scale-[0.99] transition-all duration-200 text-sm">
                Sign In
            </button>
        </form>

        <!-- Sandbox Account Helpers Quick login details -->
        <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-800">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 text-center mb-3">Seeded Logins</h3>
            <div class="space-y-2 text-[11px] text-slate-500 dark:text-slate-400">
                <!-- Admin Row -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between p-2.5 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-slate-850/50 gap-2">
                    <div class="flex items-center gap-2">
                        <span class="px-1.5 py-0.5 rounded bg-purple-50 dark:bg-purple-950/20 text-purple-600 dark:text-purple-400 font-bold text-[9px] uppercase">Admin</span>
                        <span class="font-bold text-slate-700 dark:text-slate-350">Joseph Cooper</span>
                    </div>
                    <span class="font-mono text-[10px] text-slate-500 dark:text-slate-450">admin@gmail.com <span class="text-[9px] text-slate-400">(pass: password)</span></span>
                </div>
                <!-- Manager Row -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between p-2.5 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-slate-850/50 gap-2">
                    <div class="flex items-center gap-2">
                        <span class="px-1.5 py-0.5 rounded bg-violet-50 dark:bg-violet-950/20 text-violet-650 dark:text-violet-400 font-bold text-[9px] uppercase">Manager</span>
                        <span class="font-bold text-slate-700 dark:text-slate-350">Amelia Brand</span>
                    </div>
                    <span class="font-mono text-[10px] text-slate-500 dark:text-slate-450">manager-1@company.com <span class="text-[9px] text-slate-400">(pass: password)</span></span>
                </div>
                <!-- Employee Row -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between p-2.5 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-slate-850/50 gap-2">
                    <div class="flex items-center gap-2">
                        <span class="px-1.5 py-0.5 rounded bg-sky-50 dark:bg-sky-950/20 text-skyAccent dark:text-sky-400 font-bold text-[9px] uppercase">Employee</span>
                        <span class="font-bold text-slate-700 dark:text-slate-350">Charlotte Murphy 1</span>
                    </div>
                    <span class="font-mono text-[10px] text-slate-500 dark:text-slate-450">charlotte.murphy.1@company.com <span class="text-[9px] text-slate-400">(pass: password)</span></span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
