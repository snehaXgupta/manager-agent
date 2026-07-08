<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PerformanceReport;
use App\Services\LeaderboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LeaderboardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_build_leaderboard_rankings(): void
    {
        // Create 2 managers
        $manager1 = User::create([
            'name' => 'Sarah',
            'email' => 'sarah@example.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
        ]);

        $manager2 = User::create([
            'name' => 'Sneha',
            'email' => 'sneha@example.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
        ]);

        // Seed current week reports (so leaderboard gets created immediately)
        PerformanceReport::create([
            'manager_id' => $manager1->id,
            'report_type' => 'weekly',
            'period_start' => Carbon::now()->subDays(6),
            'period_end' => Carbon::now(),
            'metrics_json' => ['manager_score' => 90.0],
            'manager_score' => 90.0,
            'generated_at' => Carbon::now(),
        ]);

        PerformanceReport::create([
            'manager_id' => $manager2->id,
            'report_type' => 'weekly',
            'period_start' => Carbon::now()->subDays(6),
            'period_end' => Carbon::now(),
            'metrics_json' => ['manager_score' => 95.0],
            'manager_score' => 95.0,
            'generated_at' => Carbon::now(),
        ]);

        $leaderboardService = app(LeaderboardService::class);
        $leaderboard = $leaderboardService->getLeaderboard('weekly');

        $this->assertEquals('weekly', $leaderboard['period']);
        $this->assertCount(2, $leaderboard['rankings']);

        // Assert Sneha is rank 1 (score 95) and Sarah is rank 2 (score 90)
        $this->assertEquals('Sneha', $leaderboard['rankings'][0]['manager_name']);
        $this->assertEquals(1, $leaderboard['rankings'][0]['rank']);
        $this->assertEquals(95.0, $leaderboard['rankings'][0]['manager_score']);

        $this->assertEquals('Sarah', $leaderboard['rankings'][1]['manager_name']);
        $this->assertEquals(2, $leaderboard['rankings'][1]['rank']);
        $this->assertEquals(90.0, $leaderboard['rankings'][1]['manager_score']);
    }

    public function test_leaderboard_caches_results(): void
    {
        $manager = User::create([
            'name' => 'Sarah',
            'email' => 'sarah@example.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
        ]);

        PerformanceReport::create([
            'manager_id' => $manager->id,
            'report_type' => 'weekly',
            'period_start' => Carbon::now()->subDays(6),
            'period_end' => Carbon::now(),
            'metrics_json' => ['manager_score' => 85.0],
            'manager_score' => 85.0,
            'generated_at' => Carbon::now(),
        ]);

        $leaderboardService = app(LeaderboardService::class);
        
        // Ensure cache is empty
        Cache::forget('leaderboard_weekly');

        // First call - builds and caches
        $leaderboard1 = $leaderboardService->getLeaderboard('weekly');
        $this->assertTrue(Cache::has('leaderboard_weekly'));

        // Modify database record to verify that subsequent call fetches from cache instead
        PerformanceReport::where('manager_id', $manager->id)->update(['manager_score' => 99.0]);

        $leaderboard2 = $leaderboardService->getLeaderboard('weekly');
        $this->assertEquals(85.0, $leaderboard2['rankings'][0]['manager_score']); // cached score

        // Clear cache and verify update
        $leaderboardService->clearCache('weekly');
        $leaderboard3 = $leaderboardService->getLeaderboard('weekly');
        $this->assertEquals(99.0, $leaderboard3['rankings'][0]['manager_score']); // new score loaded
    }

    public function test_leaderboard_api_endpoint_returns_json(): void
    {
        $manager = User::create([
            'name' => 'Sarah',
            'email' => 'sarah@example.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
        ]);

        PerformanceReport::create([
            'manager_id' => $manager->id,
            'report_type' => 'weekly',
            'period_start' => Carbon::now()->subDays(6),
            'period_end' => Carbon::now(),
            'metrics_json' => ['manager_score' => 85.0],
            'manager_score' => 85.0,
            'generated_at' => Carbon::now(),
        ]);

        // Authenticate request
        $token = $this->createDeveloperTokenForUser($manager);

        $response = $this->getJson('/api/leaderboard?period=weekly', [
            'Authorization' => "Bearer $token",
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'period',
            'date_range' => ['start', 'end'],
            'rankings' => [
                '*' => [
                    'rank',
                    'manager_name',
                    'manager_score',
                    'rank_change',
                    'score_trend',
                ]
            ]
        ]);
    }
}
