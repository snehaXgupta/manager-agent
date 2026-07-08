<?php

namespace Database\Factories;

use App\Models\TimeEntry;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimeEntryFactory extends Factory
{
    protected $model = TimeEntry::class;

    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-18 days', 'now');
        $duration = fake()->numberBetween(1800, 28800); // 30 mins to 8 hours
        $stoppedAt = (clone $startedAt)->modify("+{$duration} seconds");

        return [
            'task_id' => Task::inRandomOrder()->first()?->id ?? Task::factory(),
            'user_id' => User::where('role', 'employee')->inRandomOrder()->first()?->id ?? User::factory(),
            'started_at' => $startedAt,
            'stopped_at' => $stoppedAt,
            'duration_seconds' => $duration,
        ];
    }
}
