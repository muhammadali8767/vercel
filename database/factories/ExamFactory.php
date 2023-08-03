<?php

namespace Database\Factories;

use App\Models\Level;
use App\Models\Theme;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Exam>
 */
class ExamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $user_id = User::all()->random()->id;
        $theme_id = Theme::all()->random()->id;
        $startTime = Carbon::parse(fake()->dateTimeBetween('-1 week', '-1 day'));

        return [
            'user_id' => $user_id,
            'theme_id' => $theme_id,
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'expire_time' => $startTime->addMinutes(30)->format('Y-m-d H:i:s'),
        ];
    }
}
