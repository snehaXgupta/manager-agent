<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TeamHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamHealthServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_calculate_team_health_score(): void
    {
        $manager = User::create([
            'name' => 'Sarah',
            'email' => 'sarah@example.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
        ]);

        $employee = User::create([
            'name' => 'Rahul',
            'email' => 'rahul@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'manager_id' => $manager->id,
        ]);

        $healthService = app(TeamHealthService::class);
        $health = $healthService->calculateTeamHealth($manager->id);

        $this->assertArrayHasKey('team_health_score', $health);
        $this->assertArrayHasKey('status', $health);
        $this->assertArrayHasKey('attendance_health', $health['metrics']);
    }
}
