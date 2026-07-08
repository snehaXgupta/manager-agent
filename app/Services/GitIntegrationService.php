<?php

namespace App\Services;

use App\Models\User;
use App\Models\Task;
use App\Models\DeveloperActivity;
use Illuminate\Support\Carbon;

class GitIntegrationService
{
    /**
     * Ingest webhook data from various platforms.
     */
    public function ingestWebhookEvent(string $platform, array $payload): ?array
    {
        $platform = strtolower($platform);
        $activities = [];

        if ($platform === 'github') {
            $activities = $this->parseGithubPayload($payload);
        } elseif ($platform === 'gitlab') {
            $activities = $this->parseGitlabPayload($payload);
        } elseif ($platform === 'bitbucket') {
            $activities = $this->parseBitbucketPayload($payload);
        }

        $savedActivities = [];
        foreach ($activities as $actData) {
            // Check if author username corresponds to a local user
            $user = User::where($platform . '_username', $actData['git_username'])->first();
            if ($user) {
                $activity = DeveloperActivity::create([
                    'user_id' => $user->id,
                    'platform' => $platform,
                    'event_type' => $actData['event_type'],
                    'repository' => $actData['repository'],
                    'reference_id' => $actData['reference_id'],
                    'details_json' => $actData['details'],
                    'occurred_at' => $actData['occurred_at'],
                ]);

                // Post-processing: check for task references in commits or PRs
                $this->processTaskLinking($activity);
                $savedActivities[] = $activity;
            }
        }

        return $savedActivities;
    }

    /**
     * Parse GitHub payload structures.
     */
    protected function parseGithubPayload(array $payload): array
    {
        $events = [];
        $repository = $payload['repository']['full_name'] ?? 'unknown/repo';

        // 1. Commit/Push event
        if (isset($payload['commits']) && is_array($payload['commits'])) {
            foreach ($payload['commits'] as $commit) {
                $authorUsername = $commit['author']['username'] ?? ($payload['pusher']['name'] ?? null);
                if (!$authorUsername) continue;

                $events[] = [
                    'git_username' => $authorUsername,
                    'event_type' => 'commit',
                    'repository' => $repository,
                    'reference_id' => $commit['id'] ?? uniqid(),
                    'occurred_at' => isset($commit['timestamp']) ? Carbon::parse($commit['timestamp']) : Carbon::now(),
                    'details' => [
                        'message' => $commit['message'] ?? '',
                        'additions' => $commit['stats']['additions'] ?? 0,
                        'deletions' => $commit['stats']['deletions'] ?? 0,
                    ],
                ];
            }
        }

        // 2. Pull Request event
        if (isset($payload['pull_request'])) {
            $pr = $payload['pull_request'];
            $authorUsername = $pr['user']['login'] ?? null;
            $action = $payload['action'] ?? 'opened';

            $eventType = 'pr_opened';
            if ($action === 'closed' && ($pr['merged'] ?? false)) {
                $eventType = 'pr_merged';
            }

            if ($authorUsername) {
                $events[] = [
                    'git_username' => $authorUsername,
                    'event_type' => $eventType,
                    'repository' => $repository,
                    'reference_id' => (string) ($pr['number'] ?? ''),
                    'occurred_at' => Carbon::parse($pr['updated_at'] ?? $pr['created_at'] ?? 'now'),
                    'details' => [
                        'title' => $pr['title'] ?? '',
                        'state' => $pr['state'] ?? 'open',
                        'commits' => $pr['commits'] ?? 0,
                        'additions' => $pr['additions'] ?? 0,
                        'deletions' => $pr['deletions'] ?? 0,
                    ],
                ];
            }
        }

        // 3. Pull Request Review event
        if (isset($payload['review'])) {
            $review = $payload['review'];
            $authorUsername = $review['user']['login'] ?? null;
            
            if ($authorUsername && ($payload['action'] ?? '') === 'submitted') {
                $events[] = [
                    'git_username' => $authorUsername,
                    'event_type' => 'review_submitted',
                    'repository' => $repository,
                    'reference_id' => (string) ($review['id'] ?? ''),
                    'occurred_at' => Carbon::parse($review['submitted_at'] ?? 'now'),
                    'details' => [
                        'state' => $review['state'] ?? 'commented',
                    ],
                ];
            }
        }

        return $events;
    }

    /**
     * Parse GitLab payload structures.
     */
    protected function parseGitlabPayload(array $payload): array
    {
        $events = [];
        $repository = $payload['repository']['path_with_namespace'] ?? 'unknown/repo';
        $objectKind = $payload['object_kind'] ?? '';

        // 1. Push event
        if ($objectKind === 'push' && isset($payload['commits']) && is_array($payload['commits'])) {
            $authorUsername = $payload['user_username'] ?? null;
            if ($authorUsername) {
                foreach ($payload['commits'] as $commit) {
                    $events[] = [
                        'git_username' => $authorUsername,
                        'event_type' => 'commit',
                        'repository' => $repository,
                        'reference_id' => $commit['id'] ?? uniqid(),
                        'occurred_at' => isset($commit['timestamp']) ? Carbon::parse($commit['timestamp']) : Carbon::now(),
                        'details' => [
                            'message' => $commit['message'] ?? '',
                            'additions' => 0,
                            'deletions' => 0,
                        ],
                    ];
                }
            }
        }

        // 2. Merge Request event
        if ($objectKind === 'merge_request' && isset($payload['object_attributes'])) {
            $attrs = $payload['object_attributes'];
            $authorUsername = $payload['user']['username'] ?? null;
            $action = $attrs['action'] ?? '';

            $eventType = 'pr_opened';
            if ($action === 'merge') {
                $eventType = 'pr_merged';
            }

            if ($authorUsername) {
                $events[] = [
                    'git_username' => $authorUsername,
                    'event_type' => $eventType,
                    'repository' => $repository,
                    'reference_id' => (string) ($attrs['iid'] ?? ''),
                    'occurred_at' => Carbon::parse($attrs['updated_at'] ?? $attrs['created_at'] ?? 'now'),
                    'details' => [
                        'title' => $attrs['title'] ?? '',
                        'state' => $attrs['state'] ?? 'opened',
                    ],
                ];
            }
        }

        return $events;
    }

    /**
     * Parse Bitbucket payload structures.
     */
    protected function parseBitbucketPayload(array $payload): array
    {
        $events = [];
        $repository = $payload['repository']['full_name'] ?? 'unknown/repo';

        // 1. Push event
        if (isset($payload['push']['changes']) && is_array($payload['push']['changes'])) {
            $actor = $payload['actor']['username'] ?? null;
            if ($actor) {
                foreach ($payload['push']['changes'] as $change) {
                    $commits = $change['commits'] ?? [];
                    foreach ($commits as $commit) {
                        $events[] = [
                            'git_username' => $actor,
                            'event_type' => 'commit',
                            'repository' => $repository,
                            'reference_id' => $commit['hash'] ?? uniqid(),
                            'occurred_at' => isset($commit['date']) ? Carbon::parse($commit['date']) : Carbon::now(),
                            'details' => [
                                'message' => $commit['message'] ?? '',
                                'additions' => 0,
                                'deletions' => 0,
                            ],
                        ];
                    }
                }
            }
        }

        // 2. Pull Request event
        if (isset($payload['pullrequest'])) {
            $pr = $payload['pullrequest'];
            $actor = $payload['actor']['username'] ?? null;
            
            // Check if PR is merged or opened
            $state = strtolower($pr['state'] ?? 'open');
            $eventType = 'pr_opened';
            if ($state === 'merged') {
                $eventType = 'pr_merged';
            }

            if ($actor) {
                $events[] = [
                    'git_username' => $actor,
                    'event_type' => $eventType,
                    'repository' => $repository,
                    'reference_id' => (string) ($pr['id'] ?? ''),
                    'occurred_at' => Carbon::parse($pr['updated_on'] ?? $pr['created_on'] ?? 'now'),
                    'details' => [
                        'title' => $pr['title'] ?? '',
                        'state' => $pr['state'] ?? 'OPEN',
                    ],
                ];
            }
        }

        return $events;
    }

    /**
     * Auto-transitions a Task to completed if commit or PR references it.
     */
    protected function processTaskLinking(DeveloperActivity $activity): void
    {
        $text = '';
        if ($activity->event_type === 'commit') {
            $text = $activity->details_json['message'] ?? '';
        } elseif ($activity->event_type === 'pr_opened' || $activity->event_type === 'pr_merged') {
            $text = $activity->details_json['title'] ?? '';
        }

        if (empty($text)) return;

        // Pattern matching: "fixes #123", "closes #123", "resolves #123"
        // Also supports simple "#123" tag
        if (preg_match('/(?:fixes|closes|resolves)?\s*#(\d+)/i', $text, $matches)) {
            $taskId = (int) $matches[1];
            $task = Task::find($taskId);

            if ($task) {
                // Ensure task is assigned to the activity user or manager
                if ((int)$task->assigned_to === (int)$activity->user_id) {
                    $task->update(['status' => 'completed']);
                }
            }
        }
    }

    /**
     * Poll API activity for a specific user as a background fallback.
     */
    public function pollApiActivityForUser(User $user): void
    {
        // Fallback polling sync logic: can hit platform APIs (GitHub, GitLab, Bitbucket)
        // using configured access tokens and parse recent events.
        // Stubbed out as webhook-first is primary real-time mechanism.
    }
}
