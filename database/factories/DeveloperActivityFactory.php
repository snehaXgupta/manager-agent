<?php

namespace Database\Factories;

use App\Models\DeveloperActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeveloperActivityFactory extends Factory
{
    protected $model = DeveloperActivity::class;

    public function definition(): array
    {
        return [
            'user_id' => User::where('role', 'employee')->inRandomOrder()->first()?->id ?? User::factory(),
            'platform' => fake()->randomElement(['github', 'gitlab', 'bitbucket']),
            'event_type' => fake()->randomElement(['commit', 'pr_opened', 'pr_merged', 'review_submitted']),
            'repository' => 'org/repo-' . fake()->numberBetween(1, 1000),
            'reference_id' => fake()->sha1(),
            'details_json' => [],
            'occurred_at' => fake()->dateTimeBetween('-18 days', 'now'),
        ];
    }
}
