<?php

namespace Database\Factories;

use App\Models\RiskAlert;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RiskAlertFactory extends Factory
{
    protected $model = RiskAlert::class;

    public function definition(): array
    {
        return [
            'employee_id' => User::where('role', 'employee')->inRandomOrder()->first()?->id ?? User::factory(),
            'risk_level' => fake()->randomElement(['low', 'medium', 'high']),
            'risk_type' => fake()->randomElement(['burnout', 'deadline', 'engagement', 'performance']),
            'reason' => fake()->sentence(),
            'metrics_json' => [],
            'detected_at' => fake()->dateTime(),
            'is_resolved' => fake()->boolean(),
        ];
    }
}
