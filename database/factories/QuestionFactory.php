<?php

namespace Database\Factories;

use App\Enums\AnswersEnum;
use App\Models\Level;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'question' => fake()->text(),
            'a' => fake()->text(),
            'b' => fake()->text(),
            'c' => fake()->text(),
            'd' => fake()->text(),
            'correct' => fake()->randomElement(AnswersEnum::cases()),
            'theme_id' => Theme::all()->random()->id,
            'level_id' => Level::all()->random()->id,
        ];
    }
}
