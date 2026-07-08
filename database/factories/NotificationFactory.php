<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'type' => fake()->randomElement(['burnout_risk', 'missed_deadline', 'low_attendance', 'productivity_decline', 'ai_recommendation']),
            'severity' => fake()->randomElement(['INFO', 'WARNING', 'CRITICAL']),
            'title' => fake()->sentence(3),
            'message' => fake()->paragraph(),
            'is_read' => fake()->boolean(),
        ];
    }
}
