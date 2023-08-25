<?php

namespace App\Http\Services;

use App\Enums\ExamStatusEnum;
use App\Enums\IsCorrectEnum;
use App\Http\Repositories\ExamRepository;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\KeyWord;
use App\Models\Level;
use App\Models\Question;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ExamService implements BaseService
{
    private $examRepository;
    private $currentTime;

    public function __construct (ExamRepository $examRepository) {
        $this->examRepository = $examRepository;
        $this->currentTime = Carbon::now()->setTimezone('Asia/Tashkent')->format('Y-m-d H:i:s');

    }

    public function getActiveExamById($exam_id) {
        $exam = $this->examRepository->getActiveExamById($exam_id);
        return $exam;
    }

    public function checkUserActiveExamExists() {
        return Exam::where('user_id', Auth::id())
            ->where('status', ExamStatusEnum::ACTIVE)
            ->where('expire_time', '>', $this->currentTime)
            ->exists();
    }

    public function createExam($theme_id) {
        return Exam::create([
            'user_id' => Auth::id(),
            'theme_id' => $theme_id,
            'start_time' => $this->currentTime,
        ]);
    }

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
        if (ExamQuestion::where('exam_id', $exam->id)->exists())
            ExamQuestion::create([
                'exam_id' => $exam->id,
                'level_id' => $level->id,
                'question_id' => $question->id,
                'key_usage' => 1,
            ]);
        else {
            ExamQuestion::create([
                'exam_id' => $exam->id,
                'level_id' => $level->id,
                'question_id' => $question->id,
                'key_usage' => 1,
                'start_time' => $exam->start_time,
                'expire_time' => Carbon::parse($exam->start_time)->addSeconds(env('QUESTION_DURATION'))->format('Y-m-d H:i:s'),
            ]);
        }
    }

    public function getNextQuestion($exam_id) {
        return $this->examRepository->getNextQuestion($exam_id);
    }

    public function completeExam($exam) {
        $exam->status = ExamStatusEnum::COMPLETED;
        $exam->expire_time = $this->currentTime;
        $exam->correct_answers = $this->examRepository->getCorrectAnswers($exam->id);
        $exam->save();
        $exam->load('questionsWithCorrect', 'theme');
        return $exam;
    }

    public function getUserExamWithQuestion($exam_id,$question_id) {
        return $this->examRepository->getUserExamWithQuestion($exam_id, $question_id);
    }

    public function getUserExams() {
        return $this->examRepository->getUserExams($this->currentTime);
    }

    public function getActiveExams() {
        return $this->examRepository->getActiveExams();
    }

    public function getExamResult($exam_id) {
        return $this->examRepository->getExamResult($exam_id);
    }
}
