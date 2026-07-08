<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GitlabWebhookService;
use Illuminate\Http\Request;

class GitlabWebhookController extends Controller
{
    protected $webhookService;

    public function __construct(GitlabWebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Handle incoming GitLab webhooks.
     */
    public function handle(Request $request)
    {
        $event = $this->webhookService->handleWebhook($request);

        if (!$event) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized or signature verification failed.'
            ], 401);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Event ingested and dispatched successfully.',
            'event_id' => $event->id,
        ], 200);
    }
}
