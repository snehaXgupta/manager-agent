<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use App\Models\ProjectMember;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitlabMemberService
{
    protected $token;
    protected $baseUrl;

    public function __construct()
    {
        $this->token = config('services.gitlab.token');
        $this->baseUrl = config('services.gitlab.base_url', 'https://gitlab.com/api/v4');
    }

    /**
     * Verify employee exists in GitLab and returns details.
     */
    public function verifyUser(string $usernameOrEmail): ?array
    {
        if (empty($this->token) && !app()->runningUnitTests()) {
            // Mock return for local testing if credentials are not configured
            return [
                'id' => rand(1000, 99999),
                'username' => str_contains($usernameOrEmail, '@') ? explode('@', $usernameOrEmail)[0] : $usernameOrEmail,
                'email' => str_contains($usernameOrEmail, '@') ? $usernameOrEmail : "{$usernameOrEmail}@example.com",
            ];
        }

        try {
            // Check if it looks like an email or username
            $queryParam = str_contains($usernameOrEmail, '@') ? ['search' => $usernameOrEmail] : ['username' => $usernameOrEmail];
            
            $url = "{$this->baseUrl}/users";
            $response = Http::withToken($this->token)
                ->get($url, $queryParam);

            if (app()->runningUnitTests()) {
                Log::debug("verifyUser URL: " . $url . " Params: " . json_encode($queryParam) . " Status: " . $response->status() . " Body: " . $response->body());
            }

            if ($response->successful()) {
                $users = $response->json();
                if (!empty($users)) {
                    $gitlabUser = $users[0];
                    return [
                        'id' => $gitlabUser['id'],
                        'username' => $gitlabUser['username'],
                        'email' => $gitlabUser['email'] ?? $usernameOrEmail,
                    ];
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('GitLab verifyUser exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Add local employee to GitLab repository as Developer.
     */
    public function addMemberToRepository(Project $project, User $user): bool
    {
        $repo = $project->repository;
        if (!$repo || !$user->gitlab_user_id) {
            return false;
        }

        if (empty($this->token)) {
            // Mock local update if offline
            $pivot = $project->members()->where('user_id', $user->id)->first();
            if ($pivot) {
                $project->members()->updateExistingPivot($user->id, [
                    'gitlab_member_id' => rand(100000, 999999)
                ]);
            }
            return true;
        }

        try {
            // GitLab Access Level 30 = Developer
            $response = Http::withToken($this->token)
                ->post("{$this->baseUrl}/projects/{$repo->gitlab_project_id}/members", [
                    'user_id' => $user->gitlab_user_id,
                    'access_level' => 30, // Developer
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $project->members()->updateExistingPivot($user->id, [
                    'gitlab_member_id' => $data['id']
                ]);
                return true;
            } elseif ($response->status() === 409) {
                // User is already a member, let's fetch membership details to store the gitlab_member_id
                $this->fetchAndStoreMemberId($project, $user);
                return true;
            }

            Log::error("Failed to add member {$user->name} to GitLab project {$repo->repository_name}: " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('GitLab addMemberToRepository exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove employee from GitLab repository.
     */
    public function removeMemberFromRepository(Project $project, User $user): bool
    {
        $repo = $project->repository;
        if (!$repo || !$user->gitlab_user_id) {
            return false;
        }

        if (empty($this->token)) {
            return true;
        }

        try {
            $response = Http::withToken($this->token)
                ->delete("{$this->baseUrl}/projects/{$repo->gitlab_project_id}/members/{$user->gitlab_user_id}");

            if ($response->successful()) {
                return true;
            }

            Log::error("Failed to remove member {$user->name} from GitLab: " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('GitLab removeMemberFromRepository exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Sync project memberships with GitLab repository.
     */
    public function syncMembers(Project $project): void
    {
        $repo = $project->repository;
        if (!$repo) {
            return;
        }

        // Get local project users with gitlab_user_id
        $localMembers = $project->members()->whereNotNull('gitlab_user_id')->get();

        foreach ($localMembers as $user) {
            $pivot = $user->pivot;
            if (!$pivot || !$pivot->gitlab_member_id) {
                $this->addMemberToRepository($project, $user);
            }
        }
    }

    /**
     * Fetch and store gitlab_member_id if user is already added on GitLab side.
     */
    protected function fetchAndStoreMemberId(Project $project, User $user): void
    {
        $repo = $project->repository;
        try {
            $response = Http::withToken($this->token)
                ->get("{$this->baseUrl}/projects/{$repo->gitlab_project_id}/members/{$user->gitlab_user_id}");

            if ($response->successful()) {
                $data = $response->json();
                $project->members()->updateExistingPivot($user->id, [
                    'gitlab_member_id' => $data['id']
                ]);
            }
        } catch (\Exception $e) {
            Log::error('GitLab fetchAndStoreMemberId exception: ' . $e->getMessage());
        }
    }
}
