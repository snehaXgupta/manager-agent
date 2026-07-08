<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GitIntegrationService;
use Illuminate\Http\Request;

class GitWebhookController extends Controller
{
    protected $gitService;

    public function __construct(GitIntegrationService $gitService)
    {
        $this->gitService = $gitService;
    }

    /**
     * Handle incoming git webhooks.
     */
    public function handle(Request $request, string $platform)
    {
        $payload = $request->all();

        // Optional HMAC Signature Verification
        $secret = config('services.git.webhook_secret');
        if ($secret) {
            $signature = $request->header('X-Hub-Signature-256');
            if ($signature) {
                $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);
                if (!hash_equals($expected, $signature)) {
                    return response()->json(['error' => 'Invalid webhook signature.'], 403);
                }
            }
        }

        $result = $this->gitService->ingestWebhookEvent($platform, $payload);

        return response()->json([
            'status' => 'success',
            'events_ingested' => count($result ?? []),
            'data' => $result
        ], 200);
    }
}
