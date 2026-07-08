<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    /**
     * Display the settings workspace.
     */
    public function index()
    {
        $user = auth()->user();

        // Auto-generate webhook secret if not configured in .env
        $webhookSecret = env('FIREFLIES_WEBHOOK_SECRET');
        if (empty($webhookSecret)) {
            $webhookSecret = \Illuminate\Support\Str::random(32);
            $this->updateEnvKey('FIREFLIES_WEBHOOK_SECRET', $webhookSecret);
            config(['services.fireflies.webhook_secret' => $webhookSecret]);
        }

        return view('settings', compact('user', 'webhookSecret'));
    }

    /**
     * Update user profile information.
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id, 'ends_with:gmail.com,company.com'],
        ], [
            'email.ends_with' => 'Profile email must be a valid email address (ending with @gmail.com or @company.com).',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return redirect()->back()->with('success', 'Profile information updated successfully.');
    }

    /**
     * Update user password securely.
     */
    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password does not match your current password.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return redirect()->back()->with('success', 'Password changed successfully.');
    }

    /**
     * Update user Git platform accounts.
     */
    public function updateGitAccounts(Request $request, \App\Services\GitlabMemberService $memberService)
    {
        $user = auth()->user();

        $request->validate([
            'github_username' => ['nullable', 'string', 'max:255'],
            'gitlab_username' => ['nullable', 'string', 'max:255'],
            'bitbucket_username' => ['nullable', 'string', 'max:255'],
        ]);

        $gitlabData = [];
        if ($request->filled('gitlab_username') && $request->gitlab_username !== $user->gitlab_username) {
            $gitlabUser = $memberService->verifyUser($request->gitlab_username);
            if (!$gitlabUser) {
                return redirect()->back()->with('error', "Could not verify GitLab account for username '{$request->gitlab_username}'.");
            }

            // Prevent duplicate mapping
            $duplicate = \App\Models\User::where('gitlab_user_id', $gitlabUser['id'])
                ->where('id', '!=', $user->id)
                ->first();
            if ($duplicate) {
                return redirect()->back()->with('error', "This GitLab account is already mapped to another user ({$duplicate->name}).");
            }

            $gitlabData = [
                'gitlab_user_id' => $gitlabUser['id'],
                'gitlab_email' => $gitlabUser['email'],
            ];
        }

        $user->update(array_merge([
            'github_username' => $request->github_username,
            'gitlab_username' => $request->gitlab_username,
            'bitbucket_username' => $request->bitbucket_username,
        ], $gitlabData));

        return redirect()->back()->with('success', 'Git platform accounts updated successfully.');
    }

    /**
     * Regenerate Fireflies webhook secret dynamically.
     */
    public function regenerateWebhookSecret()
    {
        $newSecret = \Illuminate\Support\Str::random(32);
        $this->updateEnvKey('FIREFLIES_WEBHOOK_SECRET', $newSecret);
        config(['services.fireflies.webhook_secret' => $newSecret]);

        return redirect()->back()->with('success', 'Fireflies webhook secret regenerated successfully.');
    }

    /**
     * Write/update a key in the local .env file.
     */
    protected function updateEnvKey(string $key, string $value): void
    {
        $path = base_path('.env');
        if (file_exists($path)) {
            $content = file_get_contents($path);
            
            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
            } else {
                $content = rtrim($content) . "\n{$key}={$value}\n";
            }
            
            file_put_contents($path, $content);
        }
    }
}
