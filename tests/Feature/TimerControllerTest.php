<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TimerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_timer_successfully(): void
    {
        $user = User::factory()->create(['role' => 'employee']);
        $task = Task::create([
            'title' => 'Test Task',
            'status' => 'pending',
            'assigned_to' => $user->id,
        ]);

        $token = $this->createDeveloperTokenForUser($user);

        $response = $this->postJson('/api/timer/start', [
            'task_id' => $task->id,
            'user_id' => $user->id,
        ], [
            'Authorization' => "Bearer $token",
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'time_entry' => [
                    'id',
                    'task_id',
                    'user_id',
                    'started_at',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('time_entries', [
            'task_id' => $task->id,
            'user_id' => $user->id,
            'stopped_at' => null
        ]);

        // Task status should transition to in_progress
        $task->refresh();
        $this->assertEquals('in_progress', $task->status);
    }

    public function test_cannot_start_timer_with_active_timer_running(): void
    {
        $user = User::factory()->create(['role' => 'employee']);
        $task = Task::create([
            'title' => 'Test Task',
            'status' => 'pending',
            'assigned_to' => $user->id,
        ]);

        // Create an existing active timer
        TimeEntry::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'started_at' => Carbon::now()->subHour(),
        ]);

        $token = $this->createDeveloperTokenForUser($user);

        $response = $this->postJson('/api/timer/start', [
            'task_id' => $task->id,
            'user_id' => $user->id,
        ], [
            'Authorization' => "Bearer $token",
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'An active timer is already running for this user. Please stop it first.'
            ]);
    }

    public function test_stop_timer_successfully(): void
    {
        $user = User::factory()->create(['role' => 'employee']);
        $task = Task::create([
            'title' => 'Test Task',
            'status' => 'in_progress',
            'assigned_to' => $user->id,
        ]);

        $startedAt = Carbon::now()->subMinutes(45);

        // Start active timer
        $timeEntry = TimeEntry::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'started_at' => $startedAt,
        ]);

        $token = $this->createDeveloperTokenForUser($user);

        $response = $this->postJson('/api/timer/stop', [
            'user_id' => $user->id,
        ], [
            'Authorization' => "Bearer $token",
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'time_entry' => [
                    'id',
                    'stopped_at',
                    'duration_seconds'
                ]
            ]);

        $timeEntry->refresh();
        $this->assertNotNull($timeEntry->stopped_at);
        // Duration should be around 45 mins (2700 seconds)
        $this->assertGreaterThanOrEqual(2700, $timeEntry->duration_seconds);
    }

    public function test_cannot_stop_timer_if_none_running(): void
    {
        $user = User::factory()->create(['role' => 'employee']);

        $token = $this->createDeveloperTokenForUser($user);

        $response = $this->postJson('/api/timer/stop', [
            'user_id' => $user->id,
        ], [
            'Authorization' => "Bearer $token",
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'No active timer found for this user.'
            ]);
    }
}
