<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Task;
use App\Models\DeveloperActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GitWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test GitHub commit push webhook registers successfully.
     */
    public function test_github_commit_webhook_ingests_correctly(): void
    {
        // Create user with linked github account
        $user = User::factory()->create([
            'role' => 'employee',
            'github_username' => 'testdeveloper',
        ]);

        $payload = [
            'repository' => [
                'full_name' => 'acme/webapp',
            ],
            'pusher' => [
                'name' => 'testdeveloper',
            ],
            'commits' => [
                [
                    'id' => 'sha123456789',
                    'timestamp' => '2026-06-18T09:00:00Z',
                    'author' => [
                        'username' => 'testdeveloper',
                    ],
                    'message' => 'Implemented new dashboard feature',
                    'stats' => [
                        'additions' => 45,
                        'deletions' => 12,
                    ],
                ]
            ]
        ];

        $response = $this->postJson('/api/webhooks/github', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'events_ingested' => 1,
            ]);

        $this->assertDatabaseHas('developer_activities', [
            'user_id' => $user->id,
            'platform' => 'github',
            'event_type' => 'commit',
            'repository' => 'acme/webapp',
            'reference_id' => 'sha123456789',
        ]);
    }

    /**
     * Test commit message automatic task closure.
     */
    public function test_commit_closes_referenced_task(): void
    {
        $user = User::factory()->create([
            'role' => 'employee',
            'github_username' => 'octocat',
        ]);

        $task = Task::create([
            'title' => 'Important Bugfix',
            'status' => 'pending',
            'assigned_to' => $user->id,
        ]);

        $payload = [
            'repository' => [
                'full_name' => 'acme/webapp',
            ],
            'commits' => [
                [
                    'id' => 'shabcde',
                    'timestamp' => '2026-06-18T09:30:00Z',
                    'author' => [
                        'username' => 'octocat',
                    ],
                    'message' => "Fixed security bug. Resolves #{$task->id}",
                ]
            ]
        ];

        $response = $this->postJson('/api/webhooks/github', $payload);

        $response->assertStatus(200);

        // Task should automatically be updated to completed
        $task->refresh();
        $this->assertEquals('completed', $task->status);
    }

    /**
     * Test GitHub PR webhook merges register.
     */
    public function test_github_pr_merge_ingests_correctly(): void
    {
        $user = User::factory()->create([
            'role' => 'employee',
            'github_username' => 'octocat',
        ]);

        $payload = [
            'action' => 'closed',
            'repository' => [
                'full_name' => 'acme/webapp',
            ],
            'pull_request' => [
                'number' => 104,
                'title' => 'Added git integration metrics',
                'state' => 'closed',
                'merged' => true,
                'user' => [
                    'login' => 'octocat',
                ],
                'commits' => 3,
                'additions' => 120,
                'deletions' => 15,
                'updated_at' => '2026-06-18T09:45:00Z',
            ]
        ];

        $response = $this->postJson('/api/webhooks/github', $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('developer_activities', [
            'user_id' => $user->id,
            'platform' => 'github',
            'event_type' => 'pr_merged',
            'reference_id' => '104',
        ]);
    }
}
