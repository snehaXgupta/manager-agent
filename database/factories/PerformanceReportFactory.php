<?php

namespace Database\Factories;

use App\Models\PerformanceReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PerformanceReportFactory extends Factory
{
    protected $model = PerformanceReport::class;

    public function definition(): array
    {
        return [
            'manager_id' => User::where('role', 'manager')->inRandomOrder()->first()?->id ?? User::factory(),
            'report_type' => fake()->randomElement(['weekly', 'monthly']),
            'period_start' => fake()->date(),
            'period_end' => fake()->date(),
            'metrics_json' => [],
            'ai_insights_json' => [],
            'manager_score' => fake()->randomFloat(2, 50, 100),
            'generated_at' => fake()->dateTime(),
        ];
    }
}
