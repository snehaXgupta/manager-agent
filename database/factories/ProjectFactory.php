<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Project',
            'description' => fake()->paragraph(),
            'manager_id' => User::where('role', 'manager')->inRandomOrder()->first()?->id ?? User::factory(),
        ];
    }
}
