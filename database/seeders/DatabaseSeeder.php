<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Enums\AnswersEnum;
use App\Models\Exam;
use App\Models\Question;
use App\Models\UserAnswer;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\Theme::factory()->create(['name' => 'Matematika']);
        \App\Models\Theme::factory()->create(['name' => 'Fizika']);
        \App\Models\Theme::factory()->create(['name' => 'Tarix']);
        \App\Models\Theme::factory()->create(['name' => 'Kimyo']);

        \App\Models\Level::factory()->create(['name' => '1']);
        \App\Models\Level::factory()->create(['name' => '2']);
        \App\Models\Level::factory()->create(['name' => '3']);
        \App\Models\Level::factory()->create(['name' => '4']);

        \App\Models\Question::factory(500)->create();

        \App\Models\User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@example.com',
        ]);
        \App\Models\User::factory(10)->create();

        \App\Models\Exam::factory(20)->create();

        $exams = Exam::all();

        foreach ($exams as $exam) {
            $questions = Question::where('theme_id', $exam->theme_id)
                ->where('level_id', $exam->level_id)
                ->inRandomOrder()
                ->limit(10)
                ->get();

            foreach ($questions as $question) {
                $variants = ['a', 'b', 'c', 'd'];

                $answer = $variants[array_rand($variants)];
                $is_correct = ($question->correct == $answer) ? 1 : 0;

                \App\Models\UserAnswer::factory()->create([
                    'exam_id' => $exam->id,
                    'question_id' => $question->id,
                    'answer' => $answer,
                    'is_correct' => $is_correct,
                ]);
            }

            $exam->correct_answers = UserAnswer::where('exam_id', $exam->id)->where('is_correct', 1)->count();
            $exam->status = 'completed';

            $exam->save();
        }
    }
}
