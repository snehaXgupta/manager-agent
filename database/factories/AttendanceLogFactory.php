<?php

namespace Database\Factories;

use App\Models\AttendanceLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceLogFactory extends Factory
{
    protected $model = AttendanceLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::where('role', 'employee')->inRandomOrder()->first()?->id ?? User::factory(),
            'date' => fake()->date(),
            'check_in' => '09:00:00',
            'check_out' => '17:00:00',
            'status' => 'present',
        ];
    }
}
