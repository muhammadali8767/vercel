<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\KeyUsage;
use App\Models\KeyWord;
use App\Models\Level;
use App\Models\Question;
use App\Models\QuestionKeyWord;
use App\Models\Score;
use App\Models\Theme;
use App\Models\User;
use App\Models\ExamQuestion;
use Carbon\Carbon;
use Database\Factories\ScoreFactory;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        Theme::factory()->create(['name' => 'Matematika']);
        Theme::factory()->create(['name' => 'Fizika']);
        Theme::factory()->create(['name' => 'Tarix']);
        Theme::factory()->create(['name' => 'Kimyo']);

        Level::factory()->create(['name' => '1 - level', 'simple_question_count' => 1, 'has_image_count' => 1]);
        Level::factory()->create(['name' => '2 - level', 'simple_question_count' => 2, 'has_image_count' => 2]);
        Level::factory()->create(['name' => '3 - level', 'simple_question_count' => 3, 'has_image_count' => 3]);
        Level::factory()->create(['name' => '4 - level', 'simple_question_count' => 4, 'has_image_count' => 4]);
        Level::factory()->create(['name' => '5 - level', 'simple_question_count' => 5, 'has_image_count' => 5]);

        KeyUsage::factory()->create(['usage_type' => "Podskazkani ko'rish"]);
        KeyUsage::factory()->create(['usage_type' => "30 sekund qo'shish"]);

        KeyWord::factory(20)->create();

        $questions = Question::factory(500)->create();

        foreach ($questions as $question) {
            $keyWordCount = random_int(1, 10);
            for ($i = 1; $i <= $keyWordCount; $i++) {
                QuestionKeyWord::create([
                    'question_id' => $question->id,
                    'key_word_id' => KeyWord::all()->random()->id,
                ]);
            }
        }

        $roleAdmin = Role::firstOrCreate(['guard_name' => 'api', 'name' => 'admin']);
        $roleUser = Role::firstOrCreate(['guard_name' => 'api', 'name' => 'user']);

        $admin = User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@example.com',
        ]);

        $admin->syncRoles($roleAdmin, $roleUser);

        User::factory(10)->create();

        Score::factory(20)->create();

        $this->createExams();

    }

    private function createExams () {
        $scores = Score::all();
        foreach ($scores as $score) {
            $startDate = strtotime($score->start_time);
            for ($i = 1; $i <=5; $i++) {

                $exam = Exam::create([
                    'level_id' => $i,
                    'score_id' => $score->id,
                    'start_time' => date('Y-m-d H:i:s', $startDate),
                ]);

                $startDate = strtotime($exam->start_time);
                $userAnswerInSeconds = random_int(0, 60);
                $examEnds = 0;

                $keyWords = [KeyWord::all()->random()->id];

                $hasImageQuestions = Question::where('theme_id', $score->theme_id)
                    ->where('level_id', $exam->level_id)
                    ->whereHas('keyWords', function ($q) use ($keyWords) {
                        $q->whereIn('key_word_id', $keyWords);
                    })
                    ->where('has_image', 1)
                    ->inRandomOrder()
                    ->limit($exam->level->has_image_count)
                    ->get();

                $simpleQuestions = Question::where('theme_id', $score->theme_id)
                    ->where('level_id', $exam->level_id)
                    ->whereHas('keyWords', function ($q) use ($keyWords) {
                        $q->whereIn('key_word_id', $keyWords);
                    })
                    ->where('has_image', 0)
                    ->inRandomOrder()
                    ->limit($exam->level->simple_question_count)
                    ->get();

                foreach ($hasImageQuestions as $question) {
                    $this->createAnswer($score, $exam, $question, $userAnswerInSeconds, $startDate,$examEnds);
                }

                foreach ($simpleQuestions as $question) {
                    $this->createAnswer($score, $exam, $question, $userAnswerInSeconds, $startDate,$examEnds);
                }

                $exam->status = 'completed';
                $exam->expire_time = date('Y-m-d H:i:s', $startDate);
                $exam->duration_in_seconds = strtotime($exam->expire_time) - strtotime($exam->start_time);
//                $exam->not_used_keys = strtotime($exam->keys_count) - strtotime($exam->used_keys);
                $exam->save();

                if ($examEnds)
                    break;
            }
            $score->status = 'completed';
            $score->expire_time = date('Y-m-d H:i:s', $startDate);
            $score->duration_in_seconds = strtotime($score->expire_time) - strtotime($score->start_time);
            $score->level_id = $i;
//            $score->not_used_keys = strtotime($score->keys_count) - strtotime($score->used_keys);
            $score->save();
        }
    }


    private function createAnswer ($score, &$exam, $question, $userAnswerInSeconds, &$startDate, &$examEnds) {

        $key_usage = 0;
        $defaultQuestionTime = 30;
        list($is_correct, $answer) = $this->getUserAnswer($question);

        if ($is_correct) {
            if ($question->has_image == 1) {
                $exam->correct_answers ++;
                $score->correct_answers ++;
            } elseif($question->has_image == 0) {
                $exam->keys_count ++;
                $score->keys_count ++;
                $score->not_used_keys++;

            }
        } elseif($score->keys_count - $score->used_keys > 0) {
            list($is_correct, $answer) = $this->getUserAnswer($question);
            $exam->used_keys++;
            $score->used_keys++;
            $score->not_used_keys--;
            $key_usage = KeyUsage::all()->random()->id;
            if ($key_usage == 2)
                $defaultQuestionTime +=30;
        }

        $start_time = $examEnds == 0 ? date('Y-m-d H:i:s', $startDate) : null;
        $expire_time = $examEnds == 0 ? date('Y-m-d H:i:s', $startDate + $defaultQuestionTime) : null;
        $answer_time = $examEnds == 0 ? date('Y-m-d H:i:s', $startDate + $userAnswerInSeconds) : null;

        ExamQuestion::factory()->create([
            'exam_id' => $exam->id,
            'question_id' => $question->id,
            'answer' => $examEnds == 0 ? $answer : null,
            'is_correct' => $examEnds == 0 ? $is_correct : 0,
            'key_usage' => $key_usage,
            'start_time' => $start_time,
            'expire_time' => $expire_time,
            'answer_time' => $answer_time,
        ]);

        $startDate = $examEnds == 0 ? $startDate + $userAnswerInSeconds : $startDate;

        if ($userAnswerInSeconds > $defaultQuestionTime)
            $examEnds = 1;
    }

    private function getUserAnswer ($question) {
        $variants = ['a', 'b', 'c', 'd'];

        $answer = $variants[array_rand($variants)];
        $is_correct = ($question->correct == $answer) ? 1 : 0;
        return [$is_correct, $answer];
    }
}
