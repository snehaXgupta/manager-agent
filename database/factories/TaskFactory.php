<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(['pending', 'in_progress', 'completed']),
            'deadline' => fake()->dateTimeBetween('now', '+14 days'),
            'assigned_to' => User::where('role', 'employee')->inRandomOrder()->first()?->id ?? User::factory(),
            'team_id' => Team::inRandomOrder()->first()?->id,
        ];
    }
}
