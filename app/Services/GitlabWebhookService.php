<?php

namespace App\Services;

use App\Models\GitlabEvent;
use App\Models\Repository;
use App\Jobs\ProcessGitlabWebhookJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GitlabWebhookService
{
    protected $secret;

    public function __construct()
    {
        $this->secret = config('services.gitlab.webhook_secret');
    }

    /**
     * Handle incoming webhook request.
     */
    public function handleWebhook(Request $request): ?GitlabEvent
    {
        // 1. Validate Secret Token if configured
        if (!empty($this->secret)) {
            $receivedToken = $request->header('X-Gitlab-Token');
            if (empty($receivedToken) || $receivedToken !== $this->secret) {
                Log::warning('GitLab webhook signature validation failed or missing token.');
                return null;
            }
        }

        $eventType = $request->header('X-Gitlab-Event', 'Push Hook');
        $payload = $request->all();

        // 2. Resolve Repository and Project
        $gitlabProjectId = $payload['project']['id'] ?? ($payload['project_id'] ?? null);
        $repository = null;
        if ($gitlabProjectId) {
            $repository = Repository::where('gitlab_project_id', $gitlabProjectId)->first();
        }

        // 3. Store raw event in database for auditing
        $gitlabEvent = GitlabEvent::create([
            'event_type' => $eventType,
            'project_id' => $repository ? $repository->project_id : null,
            'repository_id' => $repository ? $repository->id : null,
            'payload_json' => $payload,
            'received_at' => now(),
        ]);

        // 4. Dispatch queued job for asynchronous processing
        ProcessGitlabWebhookJob::dispatch($gitlabEvent);

        return $gitlabEvent;
    }
}
