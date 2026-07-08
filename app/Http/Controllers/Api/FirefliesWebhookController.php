<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FirefliesWebhookPayload;
use App\Jobs\ProcessFirefliesWebhookJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FirefliesWebhookController extends Controller
{
    /**
     * Handle incoming Fireflies webhook events.
     */
    public function handle(Request $request)
    {
        $secret = config('services.fireflies.webhook_secret');
        if (empty($secret)) {
            Log::warning("Fireflies Webhook secret is not configured.");
            Cache::put('fireflies_webhook_status', 'Failed: Webhook secret not configured');
            return response()->json(['success' => false, 'message' => 'Webhook secret not configured.'], 500);
        }

        // 1. Validate Webhook Secret/Signature
        $signature = $request->header('X-Hub-Signature-256') ?: $request->header('X-Fireflies-Signature');
        $providedSecret = $request->header('X-Fireflies-Secret');

        $isValid = false;
        $rawBody = $request->getContent();

        if ($signature) {
            $hash = str_replace('sha256=', '', $signature);
            $computed = hash_hmac('sha256', $rawBody, $secret);
            $isValid = hash_equals($computed, $hash);
        } elseif ($providedSecret) {
            $isValid = hash_equals($secret, $providedSecret);
        }

        if (!$isValid) {
            Log::warning("Fireflies Webhook signature/secret verification failed.");
            Cache::put('fireflies_webhook_status', 'Failed: Signature/Secret verification failed');
            return response()->json(['success' => false, 'message' => 'Invalid signature or secret.'], 403);
        }

        // 2. Parse payload and retrieve identifier
        $payload = $request->all();
        $firefliesId = $payload['meetingId'] ?? $payload['id'] ?? null;
        $eventType = $payload['eventType'] ?? 'Transcription completed';

        Log::info("Fireflies Webhook verified request received for meeting ID: {$firefliesId}");

        if (empty($firefliesId)) {
            Cache::put('fireflies_webhook_status', 'Failed: Missing meeting identifier');
            return response()->json(['success' => false, 'message' => 'Missing meeting identifier.'], 400);
        }

        // 3. Prevent duplicate processing (1-minute lock)
        $lockKey = "fireflies_lock_{$firefliesId}";
        if (Cache::has($lockKey)) {
            Log::info("Fireflies webhook already processing or processed for meeting ID: {$firefliesId}");
            return response()->json(['success' => true, 'message' => 'Already processed or processing.'], 200);
        }
        Cache::put($lockKey, true, 60);

        // Record incoming webhook timestamp
        Cache::put('fireflies_last_webhook_received_at', now()->format('Y-m-d H:i:s'));

        // 4. Persist raw payload to database
        try {
            $payloadRecord = FirefliesWebhookPayload::create([
                'fireflies_meeting_id' => $firefliesId,
                'event_type' => $eventType,
                'payload' => $payload,
                'processed' => false,
            ]);

            // 5. Dispatch job for queue processing
            ProcessFirefliesWebhookJob::dispatch($payloadRecord);

            return response()->json([
                'success' => true,
                'message' => 'Webhook received and queued successfully.'
            ], 200);
        } catch (\Exception $e) {
            Log::error("Failed to store Fireflies Webhook payload: " . $e->getMessage());
            Cache::put('fireflies_webhook_status', 'Failed: Database storage error');
            return response()->json(['success' => false, 'message' => 'Failed to store payload.'], 500);
        }
    }
}
