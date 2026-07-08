<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Repository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitlabProjectService
{
    protected $token;
    protected $baseUrl;
    protected $webhookSecret;

    public function __construct()
    {
        $this->token = config('services.gitlab.token');
        $this->baseUrl = config('services.gitlab.base_url', 'https://gitlab.com/api/v4');
        $this->webhookSecret = config('services.gitlab.webhook_secret');
    }

    /**
     * Create a GitLab repository for a project.
     */
    public function createRepository(Project $project, string $visibility = 'private'): ?Repository
    {
        if (empty($this->token) && !app()->runningUnitTests()) {
            Log::warning('GitLab token is not configured. Creating local repository stub.');
            return $this->createLocalRepositoryStub($project, $visibility);
        }

        try {
            $response = Http::withToken($this->token)
                ->post("{$this->baseUrl}/projects", [
                    'name' => $project->name,
                    'description' => $project->description,
                    'visibility' => $visibility,
                    'initialize_with_readme' => true,
                ]);

            if ($response->failed()) {
                Log::error('Failed to create GitLab project: ' . $response->body());
                return $this->createLocalRepositoryStub($project, $visibility);
            }

            $data = $response->json();
            $gitlabProjectId = $data['id'];
            $repoUrl = $data['web_url'];

            // Store in DB
            $repo = Repository::create([
                'project_id' => $project->id,
                'gitlab_project_id' => $gitlabProjectId,
                'repository_name' => $project->name,
                'repository_url' => $repoUrl,
                'visibility' => $visibility,
            ]);

            // Configure webhook
            $this->configureWebhook($repo);

            return $repo;
        } catch (\Exception $e) {
            Log::error('GitLab Repository creation exception: ' . $e->getMessage());
            return $this->createLocalRepositoryStub($project, $visibility);
        }
    }

    /**
     * Configure GitLab webhook for a repository.
     */
    public function configureWebhook(Repository $repo): bool
    {
        if (empty($this->token)) {
            return false;
        }

        try {
            $webhookUrl = url('/api/webhooks/gitlab');
            // Ensure URL doesn't contain localhost if registering on public GitLab (though locally we can use mock)
            
            $response = Http::withToken($this->token)
                ->post("{$this->baseUrl}/projects/{$repo->gitlab_project_id}/hooks", [
                    'url' => $webhookUrl,
                    'token' => $this->webhookSecret,
                    'push_events' => true,
                    'merge_requests_events' => true,
                    'note_events' => true,
                    'job_events' => true,
                    'pipeline_events' => true,
                ]);

            if ($response->failed()) {
                Log::error('Failed to configure GitLab webhook: ' . $response->body());
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('GitLab configure webhook exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Local Stub in case GitLab token is missing or offline.
     */
    protected function createLocalRepositoryStub(Project $project, string $visibility): Repository
    {
        return Repository::create([
            'project_id' => $project->id,
            'gitlab_project_id' => rand(1000000, 9999999),
            'repository_name' => $project->name,
            'repository_url' => "https://gitlab.com/mock-org/" . strtolower(str_replace(' ', '-', $project->name)),
            'visibility' => $visibility,
        ]);
    }

    /**
     * Associate an existing GitLab repository with a project.
     */
    public function associateExisting(Project $project, string $repoUrl, int $gitlabProjectId, string $visibility = 'private'): Repository
    {
        // Extract repo name from URL if possible, or use project name
        $repoName = basename(parse_url($repoUrl, PHP_URL_PATH));
        if (str_ends_with($repoName, '.git')) {
            $repoName = substr($repoName, 0, -4);
        }
        if (empty($repoName)) {
            $repoName = $project->name;
        }

        $repo = Repository::create([
            'project_id' => $project->id,
            'gitlab_project_id' => $gitlabProjectId,
            'repository_name' => $repoName,
            'repository_url' => $repoUrl,
            'visibility' => $visibility,
        ]);

        // Attempt webhook registration
        $this->configureWebhook($repo);

        return $repo;
    }
}
