<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Team;
use App\Models\Meeting;
use App\Models\MeetingActionItem;
use App\Models\MeetingDecision;
use App\Models\MeetingTranscript;
use App\Models\FirefliesWebhookPayload;
use App\Jobs\ProcessFirefliesWebhookJob;
use App\Services\FirefliesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MeetingIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    protected $manager;
    protected $employee;
    protected $team;
    protected $webhookSecret = 'my-webhook-secret-key-123';

    protected function setUp(): void
    {
        parent::setUp();

        // Create manager
        $this->manager = User::create([
            'name' => 'Sarah Manager',
            'email' => 'sarah.manager@example.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
        ]);

        // Create team managed by Sarah
        $this->team = Team::create([
            'name' => 'Engineering Team Alpha',
            'manager_id' => $this->manager->id,
        ]);

        // Create employee in the team
        $this->employee = User::create([
            'name' => 'Rahul Employee',
            'email' => 'rahul.employee@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'manager_id' => $this->manager->id,
        ]);

        // Attach employee to the team
        $this->team->members()->attach($this->employee->id);

        // Configure default webhook secret in config
        config(['services.fireflies.webhook_secret' => $this->webhookSecret]);
        // Set env variable programmatically
        putenv("FIREFLIES_WEBHOOK_SECRET={$this->webhookSecret}");
    }

    /**
     * Test manager can schedule a meeting.
     */
    public function test_manager_can_schedule_meeting(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->postJson(route('dashboard.teams.meetings.store', $this->team->id), [
                'title' => 'Sprint Planning Kickoff',
                'description' => 'Planning tasks for Sprint 24.',
                'meeting_date' => '2026-06-25',
                'meeting_time' => '10:00:00',
                'duration' => 45,
                'meeting_link' => 'https://zoom.us/j/123456789',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);

        $this->assertDatabaseHas('meetings', [
            'title' => 'Sprint Planning Kickoff',
            'status' => 'Scheduled',
            'team_id' => $this->team->id,
            'manager_id' => $this->manager->id,
            'meeting_time' => '10:00:00',
            'duration' => 45,
            'meeting_link' => 'https://zoom.us/j/123456789',
        ]);
    }

    /**
     * Test manager can reschedule a meeting.
     */
    public function test_manager_can_reschedule_meeting(): void
    {
        $meeting = Meeting::create([
            'title' => 'Initial Sync',
            'description' => 'Weekly sync',
            'meeting_date' => '2026-06-20',
            'meeting_time' => '09:00:00',
            'duration' => 30,
            'status' => 'Scheduled',
            'team_id' => $this->team->id,
            'manager_id' => $this->manager->id,
            'created_by' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->postJson(route('dashboard.meetings.reschedule', $meeting->id), [
                'meeting_date' => '2026-06-21',
                'meeting_time' => '14:30:00',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);

        $this->assertDatabaseHas('meetings', [
            'id' => $meeting->id,
            'meeting_time' => '14:30:00',
            'status' => 'Scheduled',
        ]);
    }

    /**
     * Test manager can cancel a meeting.
     */
    public function test_manager_can_cancel_meeting(): void
    {
        $meeting = Meeting::create([
            'title' => 'Monthly Review',
            'meeting_date' => '2026-06-20',
            'meeting_time' => '11:00:00',
            'duration' => 60,
            'status' => 'Scheduled',
            'team_id' => $this->team->id,
            'manager_id' => $this->manager->id,
            'created_by' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->postJson(route('dashboard.meetings.cancel', $meeting->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'meeting' => [
                'id' => $meeting->id,
                'status' => 'Cancelled',
            ]
        ]);

        $this->assertDatabaseHas('meetings', [
            'id' => $meeting->id,
            'status' => 'Cancelled',
        ]);
    }

    /**
     * Test completing a meeting updates local status.
     */
    public function test_manager_can_complete_meeting(): void
    {
        $meeting = Meeting::create([
            'title' => 'Project Kickoff',
            'description' => 'Launching the Manager Agent project.',
            'meeting_date' => '2026-06-20',
            'meeting_time' => '10:00:00',
            'duration' => 60,
            'status' => 'Scheduled',
            'team_id' => $this->team->id,
            'manager_id' => $this->manager->id,
            'created_by' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->postJson(route('dashboard.meetings.complete', $meeting->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'meeting' => [
                'id' => $meeting->id,
                'status' => 'Completed'
            ]
        ]);

        $this->assertDatabaseHas('meetings', [
            'id' => $meeting->id,
            'status' => 'Completed',
        ]);
    }

    /**
     * Test webhook endpoint rejects requests with invalid signature.
     */
    public function test_webhook_rejects_requests_with_invalid_signature(): void
    {
        $payload = ['meetingId' => 'test-123', 'title' => 'Invalid Sig Sync'];
        $response = $this->postJson(route('api.webhooks.fireflies'), $payload, [
            'X-Hub-Signature-256' => 'sha256=invalid-signature-value'
        ]);

        $response->assertStatus(403);
        $response->assertJson(['success' => false, 'message' => 'Invalid signature or secret.']);
    }

    /**
     * Test webhook endpoint rejects requests with missing signature.
     */
    public function test_webhook_rejects_requests_with_missing_signature(): void
    {
        $payload = ['meetingId' => 'test-123', 'title' => 'Missing Sig Sync'];
        $response = $this->postJson(route('api.webhooks.fireflies'), $payload);

        $response->assertStatus(403);
    }

    /**
     * Test webhook accepts valid signature and creates payload record & dispatches job.
     */
    public function test_webhook_accepts_valid_signature_and_dispatches_job(): void
    {
        Queue::fake();

        $payload = [
            'meetingId' => 'test-meeting-webhook-1',
            'title' => 'Weekly Sync Webhook',
            'eventType' => 'Transcription completed'
        ];

        $jsonPayload = json_encode($payload);
        $signature = 'sha256=' . hash_hmac('sha256', $jsonPayload, $this->webhookSecret);

        $response = $this->postJson(route('api.webhooks.fireflies'), $payload, [
            'X-Hub-Signature-256' => $signature
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Webhook received and queued successfully.'
        ]);

        $this->assertDatabaseHas('fireflies_webhook_payloads', [
            'fireflies_meeting_id' => 'test-meeting-webhook-1',
            'event_type' => 'Transcription completed',
            'processed' => false,
        ]);

        Queue::assertPushed(ProcessFirefliesWebhookJob::class);
    }

    /**
     * Test webhook accepts valid X-Fireflies-Secret fallback header.
     */
    public function test_webhook_accepts_valid_secret_header(): void
    {
        Queue::fake();

        $payload = [
            'meetingId' => 'test-meeting-webhook-secret',
            'title' => 'Secret Header Sync',
        ];

        $response = $this->postJson(route('api.webhooks.fireflies'), $payload, [
            'X-Fireflies-Secret' => $this->webhookSecret
        ]);

        $response->assertStatus(200);
        Queue::assertPushed(ProcessFirefliesWebhookJob::class);
    }

    /**
     * Test Processing job stores the webhook payload and meeting details in DB.
     */
    public function test_process_webhook_job_persists_meeting_details(): void
    {
        $payload = [
            'meetingId' => 'fireflies-wh-999',
            'title' => 'Webhook Integration Sync',
            'date' => '2026-06-20T10:00:00Z',
            'duration' => 50,
            'meeting_link' => 'https://meet.google.com/abc-xyz',
            'host_email' => 'sarah.manager@example.com',
            'transcript_text' => 'This is the webhook transcript text.',
            'summary' => [
                'overview' => 'We discussed the webhook architecture.',
                'action_items' => ['Write automated tests', 'Deploy code'],
                'shorthand_bullet_points' => ['Approved the database schema']
            ],
            'participants' => ['rahul.employee@example.com'],
            'meeting_attendees' => [
                ['displayName' => 'Rahul Employee', 'email' => 'rahul.employee@example.com']
            ]
        ];

        $payloadRecord = FirefliesWebhookPayload::create([
            'fireflies_meeting_id' => 'fireflies-wh-999',
            'event_type' => 'Transcription completed',
            'payload' => $payload,
            'processed' => false,
        ]);

        // Execute Job synchronously
        $job = new ProcessFirefliesWebhookJob($payloadRecord);
        $job->handle(app(FirefliesService::class));

        // Assert payload updated
        $payloadRecord->refresh();
        $this->assertTrue($payloadRecord->processed);
        $this->assertNull($payloadRecord->error);

        // Assert Meeting created and associated
        $this->assertDatabaseHas('meetings', [
            'fireflies_meeting_id' => 'fireflies-wh-999',
            'title' => 'Webhook Integration Sync',
            'duration' => 50,
            'meeting_link' => 'https://meet.google.com/abc-xyz',
            'status' => 'Completed',
            'manager_id' => $this->manager->id,
            'team_id' => $this->team->id,
        ]);

        $meeting = Meeting::where('fireflies_meeting_id', 'fireflies-wh-999')->first();

        // Assert transcript stored
        $this->assertDatabaseHas('meeting_transcripts', [
            'meeting_id' => $meeting->id,
            'transcript' => 'This is the webhook transcript text.',
            'summary' => "We discussed the webhook architecture.\n\nKey Takeaways:\nApproved the database schema",
        ]);

        // Assert action items stored
        $this->assertDatabaseHas('meeting_action_items', [
            'meeting_id' => $meeting->id,
            'action_item' => 'Write automated tests',
            'priority' => 'Medium',
        ]);

        // Assert decisions stored
        $this->assertDatabaseHas('meeting_decisions', [
            'meeting_id' => $meeting->id,
            'decision_text' => 'Approved the database schema',
        ]);

        // Assert participants stored
        $this->assertDatabaseHas('meeting_participants', [
            'meeting_id' => $meeting->id,
            'name' => 'Rahul Employee',
            'email' => 'rahul.employee@example.com',
        ]);
    }

    /**
     * Test webhook prevents duplicate processing.
     */
    public function test_webhook_prevents_duplicate_processing(): void
    {
        $payload = [
            'meetingId' => 'duplicate-id-1',
            'title' => 'Duplicate Sync Test',
        ];

        $jsonPayload = json_encode($payload);
        $signature = 'sha256=' . hash_hmac('sha256', $jsonPayload, $this->webhookSecret);

        // Send first time
        $response1 = $this->postJson(route('api.webhooks.fireflies'), $payload, [
            'X-Hub-Signature-256' => $signature
        ]);
        $response1->assertStatus(200);

        // Send second time (should return 200 immediately but skip job dispatching/processing)
        Queue::fake();
        $response2 = $this->postJson(route('api.webhooks.fireflies'), $payload, [
            'X-Hub-Signature-256' => $signature
        ]);
        $response2->assertStatus(200);
        $response2->assertJson(['success' => true, 'message' => 'Already processed or processing.']);
        Queue::assertNotPushed(ProcessFirefliesWebhookJob::class);
    }

    /**
     * Test details page showing validation failure when transcript is not yet received.
     */
    public function test_details_page_shows_no_transcript_error_if_transcript_unavailable(): void
    {
        $meeting = Meeting::create([
            'title' => 'Empty Transcript Meeting',
            'meeting_date' => '2026-06-20',
            'meeting_time' => '10:00:00',
            'duration' => 30,
            'status' => 'Completed',
            'team_id' => $this->team->id,
            'manager_id' => $this->manager->id,
            'created_by' => $this->manager->id,
            'fireflies_meeting_id' => 'no-transcript-id',
        ]);

        // Meeting has no transcript record in database

        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->get(route('dashboard.meetings.show', $meeting->id));

        $response->assertStatus(200);
        $response->assertSee('No transcript received from Fireflies.');
    }

    /**
     * Test rendering Connection Test & Diagnostic page and Webhook Debug panel.
     */
    public function test_manager_can_view_connection_test_and_debug_card(): void
    {
        Cache::put('fireflies_webhook_status', 'Success');
        Cache::put('fireflies_last_webhook_received_at', '2026-06-20 12:00:00');
        Cache::put('fireflies_last_meeting_synced_title', 'Webhook Test Meeting');

        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->get(route('dashboard.fireflies-test'));

        $response->assertStatus(200);
        $response->assertSee('Fireflies AI Webhook Diagnostics');
        $response->assertSee('Webhook Status');
        $response->assertSee('Success');
        $response->assertSee('2026-06-20 12:00:00');
        $response->assertSee('Webhook Test Meeting');
        $response->assertSee('Webhook Payloads Audit Log');
    }

    /**
     * Test manager can regenerate webhook secret.
     */
    public function test_manager_can_regenerate_webhook_secret(): void
    {
        $initialSecret = config('services.fireflies.webhook_secret');

        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->post(route('settings.fireflies.regenerate'));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Fireflies webhook secret regenerated successfully.');

        $newSecret = config('services.fireflies.webhook_secret');
        $this->assertNotEmpty($newSecret);
        $this->assertNotEquals($initialSecret, $newSecret);
    }

    /**
     * Test developer can trigger loopback test webhook.
     */
    public function test_manager_can_trigger_send_test_webhook(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->post(route('dashboard.developer.fireflies.send-test'));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Test webhook sent and processed successfully!');
    }
}
