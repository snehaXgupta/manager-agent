<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\GitIntegrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncGitActivity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(GitIntegrationService $gitService): void
    {
        $users = User::whereNotNull('github_username')
            ->orWhereNotNull('gitlab_username')
            ->orWhereNotNull('bitbucket_username')
            ->get();

        foreach ($users as $user) {
            try {
                // Fetch latest commits and pull requests
                $gitService->pollApiActivityForUser($user);
            } catch (\Exception $e) {
                // Log and continue to not block other users sync
                logger()->error("Failed to sync git activity for user {$user->id}: " . $e->getMessage());
            }
        }
    }
}
