<?php

namespace App\Jobs;

use App\Models\Meeting;
use App\Models\FirefliesWebhookPayload;
use App\Services\FirefliesService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProcessFirefliesWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $webhookPayload;

    /**
     * Create a new job instance.
     */
    public function __construct(FirefliesWebhookPayload $webhookPayload)
    {
        $this->webhookPayload = $webhookPayload;
    }

    /**
     * Execute the job.
     */
    public function handle(FirefliesService $firefliesService): void
    {
        $payload = $this->webhookPayload->payload;
        $firefliesId = $payload['meetingId'] ?? $payload['id'] ?? null;
        $title = $payload['title'] ?? 'Fireflies Synced Meeting';

        Log::info("Executing ProcessFirefliesWebhookJob for Fireflies ID: {$firefliesId}");

        if (empty($firefliesId)) {
            $error = 'Missing meetingId or id in webhook payload.';
            $this->webhookPayload->update([
                'processed' => false,
                'error' => $error,
            ]);
            Cache::put('fireflies_webhook_status', 'Failed: ' . $error);
            return;
        }

        try {
            // 1. Find or create the local meeting record
            $meeting = Meeting::where('fireflies_meeting_id', $firefliesId)->first();

            if (!$meeting) {
                // Try to find a meeting with the same title that hasn't been associated with Fireflies yet
                $meeting = Meeting::where('title', $title)
                    ->whereNull('fireflies_meeting_id')
                    ->first();
            }

            // Parse Date
            $dateVal = $payload['date'] ?? null;
            $meetingDate = null;
            $meetingTime = null;

            if ($dateVal) {
                try {
                    if (is_numeric($dateVal)) {
                        $dt = Carbon::createFromTimestampMs($dateVal);
                    } else {
                        $dt = Carbon::parse($dateVal);
                    }
                    $meetingDate = $dt->toDateString();
                    $meetingTime = $dt->toTimeString();
                } catch (\Exception $e) {
                    Log::warning("Failed to parse webhook date '{$dateVal}': " . $e->getMessage());
                }
            }

            if (!$meetingDate) {
                $meetingDate = Carbon::now()->toDateString();
                $meetingTime = Carbon::now()->toTimeString();
            }

            // Determine manager to assign (webhook contains host_email, or default to first manager)
            $managerId = null;
            if (!empty($payload['host_email'])) {
                $host = \App\Models\User::where('email', $payload['host_email'])->first();
                if ($host && $host->role === 'manager') {
                    $managerId = $host->id;
                }
            }

            if (!$managerId) {
                $managerId = \App\Models\User::where('role', 'manager')->first()->id ?? 1;
            }

            $defaultTeam = \App\Models\Team::where('manager_id', $managerId)->first();

            if ($meeting) {
                // Update existing meeting
                $meeting->update([
                    'fireflies_meeting_id' => $firefliesId,
                    'duration' => $payload['duration'] ?? $meeting->duration ?? 30,
                    'meeting_link' => $payload['meeting_link'] ?? $meeting->meeting_link,
                    'meeting_date' => $meetingDate,
                    'meeting_time' => $meetingTime,
                    'status' => 'Completed',
                ]);
            } else {
                // Create new meeting
                $meeting = Meeting::create([
                    'fireflies_meeting_id' => $firefliesId,
                    'title' => $title,
                    'description' => $payload['summary']['overview'] ?? null,
                    'meeting_date' => $meetingDate,
                    'meeting_time' => $meetingTime,
                    'duration' => $payload['duration'] ?? 30,
                    'meeting_link' => $payload['meeting_link'] ?? null,
                    'status' => 'Completed',
                    'team_id' => $defaultTeam ? $defaultTeam->id : null,
                    'manager_id' => $managerId,
                    'created_by' => $managerId,
                ]);
            }

            // 2. Sync transcript, action items, decisions, and participants
            $firefliesService->storeTranscriptData($meeting, $payload);

            // 3. Update payload status
            $this->webhookPayload->update([
                'processed' => true,
                'processed_at' => Carbon::now(),
                'error' => null,
            ]);

            // 4. Update status cache metrics for the diagnostics panel
            Cache::put('fireflies_last_webhook_received_at', Carbon::now()->format('Y-m-d H:i:s'));
            Cache::put('fireflies_last_meeting_synced_title', $meeting->title);
            Cache::put('fireflies_last_transcript_synced_id', $meeting->fireflies_meeting_id);
            Cache::put('fireflies_webhook_status', 'Success');

            Log::info("ProcessFirefliesWebhookJob completed successfully for meeting: {$meeting->title}");
        } catch (\Exception $e) {
            Log::error("Error executing ProcessFirefliesWebhookJob: " . $e->getMessage(), [
                'exception' => $e
            ]);

            $this->webhookPayload->update([
                'processed' => false,
                'error' => $e->getMessage(),
            ]);

            Cache::put('fireflies_webhook_status', 'Failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
