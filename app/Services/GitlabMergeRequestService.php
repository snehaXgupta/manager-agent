<?php

namespace App\Services;

use App\Models\Approval;
use App\Models\GitlabEvent;
use App\Models\MergeRequest;
use App\Models\Review;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitlabMergeRequestService
{
    protected $token;
    protected $baseUrl;

    public function __construct()
    {
        $this->token = config('services.gitlab.token');
        $this->baseUrl = config('services.gitlab.base_url', 'https://gitlab.com/api/v4');
    }

    /**
     * Process a merge request event from webhook.
     */
    public function processMergeRequestEvent(GitlabEvent $event): void
    {
        $payload = $event->payload_json;
        $repo = $event->repository;
        if (!$repo) {
            Log::warning("No repository found for GitLab event ID: {$event->id}");
            return;
        }

        $attrs = $payload['object_attributes'] ?? [];
        $gitlabMrId = $attrs['iid'] ?? null;
        if (!$gitlabMrId) return;

        $title = $attrs['title'] ?? 'Untitled MR';
        $description = $attrs['description'] ?? null;
        $sourceBranch = $attrs['source_branch'] ?? '';
        $targetBranch = $attrs['target_branch'] ?? '';
        $state = strtolower($attrs['state'] ?? 'opened');
        $action = strtolower($attrs['action'] ?? '');

        // Resolve author
        $authorUsername = $payload['user']['username'] ?? '';
        $authorEmail = $payload['user']['email'] ?? '';
        $employee = User::where(function ($query) use ($authorEmail, $authorUsername) {
            $query->where('gitlab_email', $authorEmail)
                  ->orWhere('email', $authorEmail)
                  ->orWhere('gitlab_username', $authorUsername);
        })->first() ?? ($repo->project->manager ?? User::first());

        // Determine local status
        $status = 'Opened';
        if ($state === 'merged' || $action === 'merge') {
            $status = 'Merged';
        } elseif ($state === 'closed' || $action === 'close') {
            $status = 'Rejected';
        } elseif ($action === 'approved' || $action === 'approval') {
            $status = 'Approved';
        }

        // Create or update local MR record
        $mr = MergeRequest::updateOrCreate(
            [
                'repository_id' => $repo->id,
                'gitlab_mr_id' => $gitlabMrId,
            ],
            [
                'project_id' => $repo->project_id,
                'employee_id' => $employee->id,
                'title' => $title,
                'description' => $description,
                'source_branch' => $sourceBranch,
                'target_branch' => $targetBranch,
                'status' => $status,
            ]
        );

        Log::info("Merge Request #{$gitlabMrId} updated to status {$status}");
    }

    /**
     * Process a note (comment) event on a Merge Request.
     */
    public function processNoteEvent(GitlabEvent $event): void
    {
        $payload = $event->payload_json;
        $repo = $event->repository;
        if (!$repo) return;

        $attrs = $payload['object_attributes'] ?? [];
        $noteableType = $attrs['noteable_type'] ?? '';
        if ($noteableType !== 'MergeRequest') {
            return; // Only care about comments on Merge Requests
        }

        // Get merge request details from payload
        $mrPayload = $payload['merge_request'] ?? [];
        $gitlabMrId = $mrPayload['iid'] ?? null;
        if (!$gitlabMrId) return;

        // Retrieve local MR
        $mr = MergeRequest::where('repository_id', $repo->id)
            ->where('gitlab_mr_id', $gitlabMrId)
            ->first();

        if (!$mr) {
            Log::warning("Merge Request #{$gitlabMrId} not found locally for comment event.");
            return;
        }

        $comment = $attrs['note'] ?? '';
        $authorUsername = $payload['user']['username'] ?? '';
        $authorEmail = $payload['user']['email'] ?? '';
        $reviewer = User::where(function ($query) use ($authorEmail, $authorUsername) {
            $query->where('gitlab_email', $authorEmail)
                  ->orWhere('email', $authorEmail)
                  ->orWhere('gitlab_username', $authorUsername);
        })->first() ?? User::first();

        // Determine review status based on comment keywords
        $status = 'Commented';
        $lowerComment = strtolower($comment);
        if (str_contains($lowerComment, 'lgtm') || str_contains($lowerComment, 'approve')) {
            $status = 'Approved';
            
            // Create local approval record
            Approval::firstOrCreate([
                'merge_request_id' => $mr->id,
                'approved_by' => $reviewer->id,
            ], [
                'approval_date' => now(),
            ]);

            $mr->update(['status' => 'Approved']);
        } elseif (str_contains($lowerComment, 'request changes') || str_contains($lowerComment, 'changes requested') || str_contains($lowerComment, 'reject')) {
            $status = 'Changes Requested';
            $mr->update(['status' => 'Rejected']);
        }

        // Create review comment record
        Review::create([
            'merge_request_id' => $mr->id,
            'reviewer_id' => $reviewer->id,
            'comment' => $comment,
            'status' => $status,
        ]);

        Log::info("Review logged for MR #{$gitlabMrId} with status {$status}");
    }

    /**
     * Approve a merge request by the manager (UI Action).
     */
    public function approveMergeRequest(MergeRequest $mr, User $manager): bool
    {
        // 1. Update local DB
        $mr->update(['status' => 'Approved']);

        // 2. Record approval locally
        Approval::firstOrCreate([
            'merge_request_id' => $mr->id,
            'approved_by' => $manager->id,
        ], [
            'approval_date' => now(),
        ]);

        // 3. Approve via GitLab API if token is configured
        $repo = $mr->repository;
        if ($repo && !empty($this->token)) {
            try {
                $response = Http::withToken($this->token)
                    ->post("{$this->baseUrl}/projects/{$repo->gitlab_project_id}/merge_requests/{$mr->gitlab_mr_id}/approve");

                if ($response->failed()) {
                    Log::error("Failed to approve MR #{$mr->gitlab_mr_id} on GitLab: " . $response->body());
                    return false;
                }
                return true;
            } catch (\Exception $e) {
                Log::error("GitLab MR approval API exception: " . $e->getMessage());
                return false;
            }
        }

        return true;
    }

    /**
     * Reject a merge request by the manager (UI Action).
     */
    public function rejectMergeRequest(MergeRequest $mr, User $manager): bool
    {
        // 1. Update local DB
        $mr->update(['status' => 'Rejected']);

        // 2. Record review locally as Changes Requested
        Review::create([
            'merge_request_id' => $mr->id,
            'reviewer_id' => $manager->id,
            'comment' => 'Rejected by manager via dashboard.',
            'status' => 'Changes Requested',
        ]);

        // 3. Post a comment on GitLab if token configured
        $repo = $mr->repository;
        if ($repo && !empty($this->token)) {
            try {
                // Post comment on GitLab
                Http::withToken($this->token)
                    ->post("{$this->baseUrl}/projects/{$repo->gitlab_project_id}/merge_requests/{$mr->gitlab_mr_id}/notes", [
                        'body' => 'This merge request was rejected by the manager.'
                    ]);
            } catch (\Exception $e) {
                Log::error("GitLab MR rejection comment exception: " . $e->getMessage());
            }
        }

        return true;
    }

    /**
     * Sync recent merge requests for a repository from the GitLab API.
     */
    public function syncMergeRequestsForRepository(Repository $repo): int
    {
        if (empty($this->token)) {
            return 0;
        }

        try {
            $response = Http::withToken($this->token)
                ->get("{$this->baseUrl}/projects/{$repo->gitlab_project_id}/merge_requests", [
                    'per_page' => 100
                ]);

            if ($response->failed()) {
                Log::error("Failed to sync merge requests for repo {$repo->id}: " . $response->body());
                return 0;
            }

            $mrs = $response->json();
            $count = 0;

            foreach ($mrs as $mrData) {
                $gitlabMrId = $mrData['iid'] ?? null;
                if (!$gitlabMrId) continue;

                $title = $mrData['title'] ?? 'Untitled MR';
                $description = $mrData['description'] ?? null;
                $sourceBranch = $mrData['source_branch'] ?? '';
                $targetBranch = $mrData['target_branch'] ?? '';
                $state = strtolower($mrData['state'] ?? 'opened');

                $authorUsername = $mrData['author']['username'] ?? '';
                $employee = User::where('gitlab_username', $authorUsername)->first() 
                    ?? ($repo->project->manager ?? User::first());

                $status = 'Opened';
                if ($state === 'merged') {
                    $status = 'Merged';
                } elseif ($state === 'closed') {
                    $status = 'Rejected';
                }

                MergeRequest::updateOrCreate(
                    [
                        'repository_id' => $repo->id,
                        'gitlab_mr_id' => $gitlabMrId,
                    ],
                    [
                        'project_id' => $repo->project_id,
                        'employee_id' => $employee->id,
                        'title' => $title,
                        'description' => $description,
                        'source_branch' => $sourceBranch,
                        'target_branch' => $targetBranch,
                        'status' => $status,
                    ]
                );

                $count++;
            }

            return $count;
        } catch (\Exception $e) {
            Log::error("Error syncing MRs for repository {$repo->id}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Sync notes/comments (reviews) for open/merged merge requests from the GitLab API.
     */
    public function syncReviewsForMergeRequests(Repository $repo): int
    {
        if (empty($this->token)) {
            return 0;
        }

        try {
            $mrs = MergeRequest::where('repository_id', $repo->id)->get();
            $count = 0;

            foreach ($mrs as $mr) {
                $response = Http::withToken($this->token)
                    ->get("{$this->baseUrl}/projects/{$repo->gitlab_project_id}/merge_requests/{$mr->gitlab_mr_id}/notes", [
                        'per_page' => 100
                    ]);

                if ($response->failed()) continue;

                $notes = $response->json();
                foreach ($notes as $noteData) {
                    if ($noteData['system'] ?? false) continue;

                    $commentId = $noteData['id'] ?? null;
                    if (!$commentId) continue;

                    $commentText = $noteData['body'] ?? '';
                    $exists = Review::where('merge_request_id', $mr->id)
                        ->where('comment', $commentText)
                        ->exists();

                    if ($exists) continue;

                    $authorUsername = $noteData['author']['username'] ?? '';
                    $reviewer = User::where('gitlab_username', $authorUsername)->first() ?? User::first();

                    $status = 'Commented';
                    $lowerComment = strtolower($commentText);
                    if (str_contains($lowerComment, 'lgtm') || str_contains($lowerComment, 'approve')) {
                        $status = 'Approved';
                        
                        Approval::firstOrCreate([
                            'merge_request_id' => $mr->id,
                            'approved_by' => $reviewer->id,
                        ], [
                            'approval_date' => Carbon::parse($noteData['created_at'] ?? now()),
                        ]);
                    } elseif (str_contains($lowerComment, 'changes requested') || str_contains($lowerComment, 'reject')) {
                        $status = 'Changes Requested';
                    }

                    Review::create([
                        'merge_request_id' => $mr->id,
                        'reviewer_id' => $reviewer->id,
                        'comment' => $commentText,
                        'status' => $status,
                        'created_at' => Carbon::parse($noteData['created_at'] ?? now()),
                    ]);

                    $count++;
                }
            }

            return $count;
        } catch (\Exception $e) {
            Log::error("Error syncing reviews for repository {$repo->id}: " . $e->getMessage());
            return 0;
        }
    }
}
