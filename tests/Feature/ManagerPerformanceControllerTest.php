<?php

namespace Tests\Feature;

use App\Models\PerformanceReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerPerformanceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_fails_if_not_a_manager(): void
    {
        // Create an employee user
        $user = User::factory()->create(['role' => 'employee']);

        $token = $this->createDeveloperTokenForUser($user);

        $response = $this->getJson("/api/managers/{$user->id}/performance", [
            'Authorization' => "Bearer $token",
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Manager not found or user does not have manager role.'
            ]);
    }

    public function test_succeeds_for_valid_manager(): void
    {
        // Create manager
        $manager = User::create([
            'name' => 'Test Manager',
            'email' => 'manager@test.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
        ]);

        // Create employee reporting to manager
        $employee = User::create([
            'name' => 'Team Member',
            'email' => 'member@test.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'manager_id' => $manager->id,
        ]);

        $token = $this->createDeveloperTokenForUser($manager);

        $response = $this->getJson("/api/managers/{$manager->id}/performance?period=weekly", [
            'Authorization' => "Bearer $token",
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'manager' => ['id', 'name', 'email'],
                'period',
                'date_range' => ['start', 'end'],
                'report_id',
                'metrics' => [
                    'team_size',
                    'task_completion_rate',
                    'deadline_adherence_rate',
                    'productivity_score',
                    'consistency_score',
                    'manager_score',
                    'metrics_breakdown' => [
                        'total_assigned_tasks',
                        'completed_tasks',
                        'completed_on_time_tasks',
                        'total_hours_logged',
                        'expected_hours',
                    ]
                ]
            ]);

        $this->assertDatabaseHas('performance_reports', [
            'manager_id' => $manager->id,
            'report_type' => 'weekly',
        ]);
    }
}
