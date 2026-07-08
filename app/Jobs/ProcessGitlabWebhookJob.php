<?php

namespace App\Jobs;

use App\Models\GitlabEvent;
use App\Services\GitlabCommitService;
use App\Services\GitlabMergeRequestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessGitlabWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $event;

    /**
     * Create a new job instance.
     */
    public function __construct(GitlabEvent $event)
    {
        $this->event = $event;
    }

    /**
     * Execute the job.
     */
    public function handle(GitlabCommitService $commitService, GitlabMergeRequestService $mrService): void
    {
        $payload = $this->event->payload_json;
        $eventType = strtolower($this->event->event_type);

        Log::info("Processing GitLab event ID {$this->event->id} of type {$eventType}");

        try {
            if ($eventType === 'push hook' || $eventType === 'push') {
                $commitService->processPushEvent($this->event);
            } elseif ($eventType === 'merge request hook' || $eventType === 'merge_request') {
                $mrService->processMergeRequestEvent($this->event);
            } elseif ($eventType === 'note hook' || $eventType === 'note') {
                $mrService->processNoteEvent($this->event);
            } else {
                Log::info("Unhandled GitLab webhook event type: {$eventType}");
            }
        } catch (\Exception $e) {
            Log::error("Error processing GitLab webhook job (Event ID: {$this->event->id}): " . $e->getMessage(), [
                'exception' => $e
            ]);
            throw $e;
        }
    }
}
