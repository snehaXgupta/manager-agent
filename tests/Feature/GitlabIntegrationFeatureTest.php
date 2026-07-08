<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Project;
use App\Models\Repository;
use App\Models\Commit;
use App\Models\MergeRequest;
use App\Models\Review;
use App\Models\Approval;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GitlabIntegrationFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $manager;
    protected $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::create([
            'name' => 'Sarah Manager',
            'email' => 'sarah@example.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
        ]);

        $this->employee = User::create([
            'name' => 'Rahul Employee',
            'email' => 'rahul@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'manager_id' => $this->manager->id,
            'gitlab_username' => 'rahul-dev',
            'gitlab_user_id' => 12345,
            'gitlab_email' => 'rahul@example.com',
        ]);
    }

    /**
     * Test mapping an employee to a GitLab account.
     */
    public function test_manager_can_map_employee_gitlab_account(): void
    {
        $baseUrl = config('services.gitlab.base_url');
        Http::fake([
            $baseUrl . '/users*' => Http::response([[
                'id' => 67890,
                'username' => 'john-dev',
                'email' => 'john@example.com'
            ]], 200)
        ]);

        $unmappedEmployee = User::create([
            'name' => 'John Dev',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'manager_id' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->post(route('dashboard.employees.map-gitlab', $unmappedEmployee->id), [
                'gitlab_username_or_email' => 'john-dev'
            ]);

        $response->assertRedirect();
        
        $unmappedEmployee->refresh();
        $this->assertEquals(67890, $unmappedEmployee->gitlab_user_id);
        $this->assertEquals('john-dev', $unmappedEmployee->gitlab_username);
    }

    /**
     * Test duplicate GitLab account mapping is prevented.
     */
    public function test_duplicate_gitlab_mapping_is_prevented(): void
    {
        $baseUrl = config('services.gitlab.base_url');
        Http::fake([
            $baseUrl . '/users*' => Http::response([[
                'id' => 12345,
                'username' => 'rahul-dev',
                'email' => 'rahul@example.com'
            ]], 200)
        ]);

        $anotherEmployee = User::create([
            'name' => 'Another Dev',
            'email' => 'another@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'manager_id' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->post(route('dashboard.employees.map-gitlab', $anotherEmployee->id), [
                'gitlab_username_or_email' => 'rahul-dev'
            ]);

        $response->assertSessionHas('error');
        $this->assertNull($anotherEmployee->refresh()->gitlab_user_id);
    }

    /**
     * Test webhook ingestion and raw logging.
     */
    public function test_gitlab_webhook_ingests_and_logs_payload(): void
    {
        config(['services.gitlab.webhook_secret' => 'supersecret']);

        $project = Project::create([
            'name' => 'Engineering Webapp',
            'manager_id' => $this->manager->id,
        ]);

        $repo = Repository::create([
            'project_id' => $project->id,
            'gitlab_project_id' => 999,
            'repository_name' => 'Engineering Webapp',
            'repository_url' => 'https://gitlab.com/acme/webapp',
        ]);

        $payload = [
            'object_kind' => 'push',
            'project_id' => 999,
            'project' => [
                'id' => 999,
                'name' => 'Engineering Webapp'
            ],
            'commits' => [
                [
                    'id' => 'sha987654321',
                    'message' => 'Integrated database tables',
                    'timestamp' => '2026-06-19T12:00:00Z',
                    'author' => [
                        'name' => 'rahul-dev',
                        'email' => 'rahul@example.com'
                    ]
                ]
            ]
        ];

        $response = $this->withHeaders([
            'X-Gitlab-Token' => 'supersecret',
            'X-Gitlab-Event' => 'Push Hook'
        ])->postJson('/api/webhooks/gitlab', $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('gitlab_events', [
            'event_type' => 'Push Hook',
            'project_id' => $project->id,
            'repository_id' => $repo->id,
        ]);
    }

    /**
     * Test manager MR approval workflow.
     */
    public function test_manager_can_approve_merge_request(): void
    {
        $project = Project::create([
            'name' => 'Engineering Webapp',
            'manager_id' => $this->manager->id,
        ]);

        $repo = Repository::create([
            'project_id' => $project->id,
            'gitlab_project_id' => 999,
            'repository_name' => 'Engineering Webapp',
            'repository_url' => 'https://gitlab.com/acme/webapp',
        ]);

        $mr = MergeRequest::create([
            'project_id' => $project->id,
            'repository_id' => $repo->id,
            'employee_id' => $this->employee->id,
            'gitlab_mr_id' => 45,
            'title' => 'Feature PR',
            'source_branch' => 'feature',
            'target_branch' => 'main',
            'status' => 'Opened'
        ]);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->post(route('dashboard.mr.approve', $mr->id));

        $response->assertRedirect();
        
        $mr->refresh();
        $this->assertEquals('Approved', $mr->status);
        $this->assertDatabaseHas('approvals', [
            'merge_request_id' => $mr->id,
            'approved_by' => $this->manager->id
        ]);
    }
}
