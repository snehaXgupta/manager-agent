<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Task;
use App\Services\WorkloadAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkloadAnalysisServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_analyze_team_workload_and_recommend_balancing(): void
    {
        $manager = User::create([
            'name' => 'Sarah',
            'email' => 'sarah@example.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
        ]);

        $employee1 = User::create([
            'name' => 'Rahul',
            'email' => 'rahul@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'manager_id' => $manager->id,
        ]);

        $employee2 = User::create([
            'name' => 'Shipra',
            'email' => 'shipra@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'manager_id' => $manager->id,
        ]);

        // Rahul gets 6 active tasks (overloaded)
        for ($i = 0; $i < 6; $i++) {
            Task::create([
                'title' => "Rahul Task {$i}",
                'status' => 'in_progress',
                'assigned_to' => $employee1->id,
            ]);
        }

        // Shipra gets 1 active task (underutilized)
        Task::create([
            'title' => "Shipra Task 1",
            'status' => 'pending',
            'assigned_to' => $employee2->id,
        ]);

        $workloadService = app(WorkloadAnalysisService::class);
        $analysis = $workloadService->analyzeWorkload($manager->id);

        $this->assertCount(2, $analysis['team_workload']);
        $this->assertEquals('Overloaded', $analysis['team_workload'][0]['status']);
        $this->assertEquals('Underutilized', $analysis['team_workload'][1]['status']);
        
        // Assert we got at least one recommendation to shift tasks
        $this->assertNotEmpty($analysis['recommendations']);
        $this->assertStringContainsString('Move', $analysis['recommendations'][0]);
    }
}
