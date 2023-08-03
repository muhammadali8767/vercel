<?php

namespace App\Http\Services;

use App\Enums\ExamStatusEnum;
use App\Enums\IsCorrectEnum;
use App\Models\ExamQuestion;
use App\Models\KeyWord;
use App\Models\Level;
use App\Models\Question;

class ExamService implements BaseService
{

    public function setExamQuestions($exam) {
        $levels = Level::all();
        foreach ($levels as $level) {
            $keyWords = [KeyWord::all()->random()->id];
            $this->assignSimpleQuestions($exam, $level, $keyWords);
            $this->assignQuestionsWithImage($exam, $level, $keyWords);
        }
    }

    public function assignSimpleQuestions ($exam, $level, $keyWords) {
        $simpleQuestions = Question::where('theme_id', $exam->theme_id)
            ->where('level_id', $level->id)
            ->whereHas('keyWords', function ($q) use ($keyWords) {
                $q->whereIn('key_word_id', $keyWords);
            })
            ->where('has_image', 0)
            ->inRandomOrder()
            ->limit($level->simple_question_count)
            ->get();

        foreach ($simpleQuestions as $question) {
            $this->assignQuestion($exam, $level, $question);
        }
    }

    public function assignQuestionsWithImage ($exam, $level, $keyWords) {
        $hasImageQuestions = Question::where('theme_id', $exam->theme_id)
            ->where('level_id', $level->id)
            ->whereHas('keyWords', function ($q) use ($keyWords) {
                $q->whereIn('key_word_id', $keyWords);
            })
            ->where('has_image', 1)
            ->inRandomOrder()
            ->limit($level->has_image_count)
            ->get();

        foreach ($hasImageQuestions as $question) {
            $this->assignQuestion($exam, $level, $question);
        }
    }

    public function assignQuestion($exam, $level, $question) {
        ExamQuestion::create([
            'exam_id' => $exam->id,
            'level_id' => $level->id,
            'question_id' => $question->id,
            'key_usage' => 1,
        ]);
    }
    public function calcExamResult($exam) {
        $exam->status = ExamStatusEnum::COMPLETED;
        $exam->expire_time = $this->currentTime;
        $exam->correct_answers = ExamQuestion::where('exam_id', $exam->id)
            ->where('is_correct', IsCorrectEnum::CORRECT)
            ->count();

        $exam->save();

        $exam->load('questionsWithCorrect', 'theme');
        return $exam;
    }
}
