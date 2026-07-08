<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
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
        ]);
    }

    /**
     * Test manager can access projects list page.
     */
    public function test_manager_can_access_projects_page(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->get(route('dashboard.projects.index'));

        $response->assertStatus(200);
        $response->assertSee('Manager Projects');
    }

    /**
     * Test manager can create a project with members.
     */
    public function test_manager_can_create_project(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->post(route('dashboard.projects.store'), [
                'name' => 'Analytics Platform V2',
                'description' => 'Rebuilding the core intelligence flow.',
                'members' => [$this->employee->id]
            ]);

        $response->assertRedirect(route('dashboard.projects.index'));
        $this->assertDatabaseHas('projects', ['name' => 'Analytics Platform V2']);
        
        $project = Project::where('name', 'Analytics Platform V2')->first();
        $this->assertTrue($project->members->contains($this->employee->id));
    }

    /**
     * Test manager can view project dashboard show page.
     */
    public function test_manager_can_view_project_dashboard(): void
    {
        $project = Project::create([
            'name' => 'Analytics Platform V2',
            'manager_id' => $this->manager->id
        ]);
        $project->members()->attach($this->employee->id);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->get(route('dashboard.projects.show', $project->id));

        $response->assertStatus(200);
        $response->assertSee('Analytics Platform V2');
        $response->assertSee('Project Task Board');
    }

    /**
     * Test manager can assign a task to a project.
     */
    public function test_manager_can_assign_task_to_project(): void
    {
        $project = Project::create([
            'name' => 'Analytics Platform V2',
            'manager_id' => $this->manager->id
        ]);
        $project->members()->attach($this->employee->id);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->post(route('dashboard.projects.tasks.store', $project->id), [
                'title' => 'Implement Analytics Chart',
                'description' => 'Use Highcharts or D3.',
                'deadline' => now()->addDays(5)->format('Y-m-d'),
                'assigned_to' => $this->employee->id
            ]);

        $response->assertRedirect(route('dashboard.projects.show', $project->id));
        $this->assertDatabaseHas('tasks', [
            'title' => 'Implement Analytics Chart',
            'project_id' => $project->id,
            'assigned_to' => $this->employee->id
        ]);
    }

    /**
     * Test manager cannot assign a task to a project if employee is not a member.
     */
    public function test_manager_cannot_assign_task_to_project_if_not_member(): void
    {
        $project = Project::create([
            'name' => 'Analytics Platform V2',
            'manager_id' => $this->manager->id
        ]);

        $nonMember = User::create([
            'name' => 'John Nonmember',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'manager_id' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->post(route('dashboard.projects.tasks.store', $project->id), [
                'title' => 'Implement Analytics Chart',
                'description' => 'Use Highcharts or D3.',
                'deadline' => now()->addDays(5)->format('Y-m-d'),
                'assigned_to' => $nonMember->id
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('tasks', [
            'title' => 'Implement Analytics Chart',
            'project_id' => $project->id
        ]);
    }

    /**
     * Test manager can create a project and link an existing repository.
     */
    public function test_manager_can_create_project_with_existing_repository(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->post(route('dashboard.projects.store'), [
                'name' => 'Existing Repo Project',
                'description' => 'Working with a pre-existing repo.',
                'members' => [$this->employee->id],
                'repo_mode' => 'existing',
                'existing_repo_url' => 'https://gitlab.com/acme/pre-existing-project',
                'existing_gitlab_project_id' => 777888
            ]);

        $response->assertRedirect(route('dashboard.projects.index'));
        $this->assertDatabaseHas('projects', ['name' => 'Existing Repo Project']);
        
        $project = Project::where('name', 'Existing Repo Project')->first();
        $this->assertNotNull($project);
        
        $this->assertDatabaseHas('repositories', [
            'project_id' => $project->id,
            'gitlab_project_id' => 777888,
            'repository_url' => 'https://gitlab.com/acme/pre-existing-project',
            'repository_name' => 'pre-existing-project',
        ]);
    }

    /**
     * Test validation fails when manager selects existing repo but provides invalid details.
     */
    public function test_manager_cannot_create_project_with_invalid_existing_repository_details(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['active_role' => 'manager'])
            ->post(route('dashboard.projects.store'), [
                'name' => 'Invalid Existing Repo Project',
                'repo_mode' => 'existing',
                'existing_repo_url' => 'not-a-url',
                'existing_gitlab_project_id' => 'not-an-integer'
            ]);

        $response->assertSessionHasErrors(['existing_repo_url', 'existing_gitlab_project_id']);
        $this->assertDatabaseMissing('projects', ['name' => 'Invalid Existing Repo Project']);
    }
}
