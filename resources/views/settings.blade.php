@extends('layouts.app')

@section('content')
<div class="space-y-8 animate-fade-in max-w-4xl" x-data="{ activeTab: 'profile' }">
    <!-- Header -->
    <div>
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Workspace Settings</h2>
        <!-- <p class="text-sm text-slate-500 dark:text-slate-400">Configure your profile details, visual preferences, and login credentials.</p> -->
    </div>

    <!-- Navigation Tabs -->
    <div class="flex border-b border-slate-200 dark:border-slate-800 gap-6">
        <button @click="activeTab = 'profile'" 
                :class="activeTab === 'profile' ? 'border-skyAccent text-skyAccent dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
                class="pb-3 border-b-2 font-semibold text-sm transition-all focus:outline-none">
            Profile Information
        </button>
        <button @click="activeTab = 'theme'" 
                :class="activeTab === 'theme' ? 'border-skyAccent text-skyAccent dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
                class="pb-3 border-b-2 font-semibold text-sm transition-all focus:outline-none">
            Theme Preferences
        </button>
        <button @click="activeTab = 'security'" 
                :class="activeTab === 'security' ? 'border-skyAccent text-skyAccent dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
                class="pb-3 border-b-2 font-semibold text-sm transition-all focus:outline-none">
            Security & Credentials
        </button>
        <button @click="activeTab = 'git'" 
                :class="activeTab === 'git' ? 'border-skyAccent text-skyAccent dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
                class="pb-3 border-b-2 font-semibold text-sm transition-all focus:outline-none">
            Git Integrations
        </button>
        <button @click="activeTab = 'fireflies'" 
                :class="activeTab === 'fireflies' ? 'border-skyAccent text-skyAccent dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
                class="pb-3 border-b-2 font-semibold text-sm transition-all focus:outline-none">
            Fireflies Integration
        </button>
    </div>

    <!-- Tab Contents -->
    
    <!-- Profile Info Tab -->
    <div x-show="activeTab === 'profile'" class="space-y-6">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Personal Details</h3>
            
            <form action="{{ route('settings.profile') }}" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1">
                        <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Full Name</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                               class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-sm text-slate-900 dark:text-white">
                        @error('name')
                            <span class="text-xs text-red-500 font-semibold mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="space-y-1">
                        <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Email Address (@gmail.com or @company.com)</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                               class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-sm text-slate-900 dark:text-white">
                        @error('email')
                            <span class="text-xs text-red-500 font-semibold mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                    <button type="submit" 
                            class="px-5 py-2.5 bg-skyAccent hover:bg-sky-500 dark:bg-blue-600 dark:hover:bg-blue-500 text-white font-bold rounded-xl shadow-sm hover:shadow active:scale-[0.98] transition-all text-xs">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Theme Preferences Tab -->
    <div x-show="activeTab === 'theme'" class="space-y-6" x-cloak>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1">Visual Theme</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-6">Choose how the application workspace looks on your screen.</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Light Theme Option -->
                <button type="button" 
                        @click="if (darkMode) { toggleDarkMode() }"
                        :class="!darkMode ? 'border-skyAccent bg-sky-50/20 ring-1 ring-skyAccent shadow-sm' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700'"
                        class="flex items-center gap-4 p-5 rounded-2xl border text-left transition-all">
                    <div class="p-3 bg-amber-50 rounded-xl text-amber-500">
                        <!-- Sun Icon -->
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 9H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M16.243 17.657l.707-.707M6.343 6.343l.707-.707M14 12a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <div>
                        <span class="block font-bold text-sm text-slate-800 dark:text-slate-200">Light Mode</span>
                        <span class="block text-xs text-slate-400 dark:text-slate-500">Bright and clean visual palette</span>
                    </div>
                </button>

                <!-- Dark Theme Option -->
                <button type="button" 
                        @click="if (!darkMode) { toggleDarkMode() }"
                        :class="darkMode ? 'border-skyAccent bg-blue-950/10 ring-1 ring-skyAccent shadow-sm' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700'"
                        class="flex items-center gap-4 p-5 rounded-2xl border text-left transition-all">
                    <div class="p-3 bg-slate-900 dark:bg-slate-850 rounded-xl text-indigo-400">
                        <!-- Moon Icon -->
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    </div>
                    <div>
                        <span class="block font-bold text-sm text-slate-800 dark:text-slate-200">Dark Mode</span>
                        <span class="block text-xs text-slate-400 dark:text-slate-500">Elegant layout optimized for low-light</span>
                    </div>
                </button>
            </div>
        </div>
    </div>

    <!-- Security & Credentials Tab -->
    <div x-show="activeTab === 'security'" class="space-y-6" x-cloak>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Change Password</h3>
            
            <form action="{{ route('settings.password') }}" method="POST" class="space-y-4">
                @csrf
                <div class="space-y-4">
                    <div class="space-y-1 max-w-md">
                        <label for="current_password" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required
                               class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-sm text-slate-900 dark:text-white">
                        @error('current_password')
                            <span class="text-xs text-red-500 font-semibold mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-1">
                            <label for="new_password" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">New Password</label>
                            <input type="password" id="new_password" name="new_password" required
                                   class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-sm text-slate-900 dark:text-white">
                            @error('new_password')
                                <span class="text-xs text-red-500 font-semibold mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="space-y-1">
                            <label for="new_password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Confirm New Password</label>
                            <input type="password" id="new_password_confirmation" name="new_password_confirmation" required
                                   class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-sm text-slate-900 dark:text-white">
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                    <button type="submit" 
                            class="px-5 py-2.5 bg-skyAccent hover:bg-sky-500 dark:bg-blue-600 dark:hover:bg-blue-500 text-white font-bold rounded-xl shadow-sm hover:shadow active:scale-[0.98] transition-all text-xs">
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Git Integrations Tab -->
    <div x-show="activeTab === 'git'" class="space-y-6" x-cloak>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1">Version Control Profiles</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-6">Link your development accounts so that your code contributions (commits, PRs, reviews) can automatically sync to track progress and metrics.</p>
            
            <form action="{{ route('settings.git') }}" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- GitHub -->
                    <div class="space-y-1">
                        <label for="github_username" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">GitHub Username</label>
                        <input type="text" id="github_username" name="github_username" value="{{ old('github_username', $user->github_username) }}" placeholder="octocat"
                               class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-sm text-slate-900 dark:text-white">
                        @error('github_username')
                            <span class="text-xs text-red-500 font-semibold mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- GitLab -->
                    <div class="space-y-1">
                        <label for="gitlab_username" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">GitLab Username</label>
                        <input type="text" id="gitlab_username" name="gitlab_username" value="{{ old('gitlab_username', $user->gitlab_username) }}" placeholder="gitlab-user"
                               class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-sm text-slate-900 dark:text-white">
                        @error('gitlab_username')
                            <span class="text-xs text-red-500 font-semibold mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Bitbucket -->
                    <div class="space-y-1">
                        <label for="bitbucket_username" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Bitbucket Username</label>
                        <input type="text" id="bitbucket_username" name="bitbucket_username" value="{{ old('bitbucket_username', $user->bitbucket_username) }}" placeholder="bb-username"
                               class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:border-skyAccent focus:ring-1 focus:ring-skyAccent text-sm text-slate-900 dark:text-white">
                        @error('bitbucket_username')
                            <span class="text-xs text-red-500 font-semibold mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                    <button type="submit" 
                            class="px-5 py-2.5 bg-skyAccent hover:bg-sky-500 dark:bg-blue-600 dark:hover:bg-blue-500 text-white font-bold rounded-xl shadow-sm hover:shadow active:scale-[0.98] transition-all text-xs">
                        Save Git Profiles
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Fireflies Integration Tab -->
    <div x-show="activeTab === 'fireflies'" class="space-y-6" x-cloak>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-6">
            <div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1">Fireflies.ai Webhook Integration</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Configure event-based updates to automatically receive transcripts, summaries, and action items directly from Fireflies when a meeting ends.</p>
            </div>

            <!-- Webhook Connection Details -->
            <div class="space-y-5 font-sans">
                <!-- Webhook URL -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Webhook URL</label>
                    <div class="flex items-center gap-2 max-w-2xl">
                        <input type="text" id="webhook_url_input" readonly value="{{ str_replace('http://', 'https://', route('api.webhooks.fireflies')) }}" 
                               class="flex-1 px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-mono text-slate-800 dark:text-slate-200 select-all outline-none">
                        <button type="button" @click="navigator.clipboard.writeText('{{ str_replace('http://', 'https://', route('api.webhooks.fireflies')) }}'); alert('Webhook URL copied!');" 
                                class="px-4 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-750 dark:text-slate-200 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 active:scale-95 transition-all">
                            Copy URL
                        </button>
                    </div>
                </div>

                <!-- Webhook Secret -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Webhook Secret</label>
                    <div class="flex items-center gap-2 max-w-2xl">
                        <input type="text" id="webhook_secret_input" readonly value="{{ $webhookSecret }}" 
                               class="flex-1 px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-mono text-slate-800 dark:text-slate-200 select-all outline-none">
                        <button type="button" @click="navigator.clipboard.writeText('{{ $webhookSecret }}'); alert('Webhook Secret copied!');" 
                                class="px-4 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-750 dark:text-slate-200 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 active:scale-95 transition-all">
                            Copy Secret
                        </button>
                    </div>
                </div>

                <!-- Regenerate Option -->
                <div class="pt-2">
                    <form action="{{ route('settings.fireflies.regenerate') }}" method="POST" onsubmit="return confirm('Regenerating will invalidate the current secret and stop incoming webhooks until updated in Fireflies. Proceed?');">
                        @csrf
                        <button type="submit" 
                                class="px-4 py-2 border border-rose-200 hover:bg-rose-50 text-rose-700 dark:border-red-900/30 dark:hover:bg-red-950/20 dark:text-red-400 text-xs font-bold rounded-xl transition-colors">
                            Regenerate Webhook Secret
                        </button>
                    </form>
                </div>
            </div>

            <!-- Setup Instructions -->
            <div class="border-t border-slate-100 dark:border-slate-800 pt-5 space-y-3.5">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Setup Instructions</h4>
                <ol class="list-decimal pl-4 text-xs text-slate-500 dark:text-slate-400 space-y-2 leading-relaxed">
                    <li>Log in to your **Fireflies.ai Developer Dashboard**.</li>
                    <li>Navigate to the **Webhooks** section.</li>
                    <li>Paste the **Webhook URL** copied above into the *Webhook URL* field.</li>
                    <li>Paste the **Webhook Secret** copied above into the *Secret key* field.</li>
                    <li>Select the **"Transcription completed"** event trigger.</li>
                    <li>Click **Save** / **Activate Webhook**.</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection
