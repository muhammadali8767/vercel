<?php

namespace App\Http\Controllers\API\V1\User;

use App\Http\Controllers\API\V1\BaseController;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\KeyUsage;
use App\Models\KeyWord;
use App\Models\Question;
use App\Models\Score;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ScoreController extends BaseController
{
    public function index()
    {

        $scores = Score::with('user','theme')
            ->withCount('exams')
            ->where('user_id', Auth::id())
            ->orderBy('correct_answers', 'desc')
            ->orderBy('keys_count', 'desc')
            ->orderBy('not_used_keys', 'desc')
            ->orderBy('level_id', 'desc')
            ->paginate(10);

        return $this->sendResponse($scores);
    }

    public function show(Score $score)
    {
        if ($score->user_id != Auth::id())
            return $this->sendError('You dont have permissions', 403);

        $score->load('examsWithQuestions');

        return $this->sendResponse($score);
    }

    public function startExam(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'theme_id' => 'required|numeric|exists:themes,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $user_id = Auth::id();

        $score = Score::where('user_id', $user_id)
            ->where('status', 'active')
            ->where('theme_id', $request->theme_id)
            ->first();

        if ($score)
            return $this->sendResponse($score);
        $startTime = Carbon::now();

        $score = Score::create([
            'user_id' => $user_id,
            'theme_id' => $request->theme_id,
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'expire_time' => $startTime->addMinutes(15)->format('Y-m-d H:i:s'),
        ]);

        $this->createExam($score);
    }

    private function getOrCreateScoreExam(Score $score, $level_id)
    {
        if (Exam::where('score_id', $score->id)->where('level_id', $level_id)->where('status', 'active')->exists())
            return Exam::where('score_id', $score->id)
                ->where('level_id', $level_id)
                ->where('status', 'active')
                ->with('questions')
                ->first();


    }

    private function createExams ($score, $level_id) {
        $startDate = strtotime($score->start_time);
        $exam = Exam::create([
            'level_id' => $level_id,
            'score_id' => $score->id,
            'start_time' => date('Y-m-d H:i:s', $startDate),
        ]);

        $startDate = strtotime($exam->start_time);
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

        $score->status = 'completed';
        $score->expire_time = date('Y-m-d H:i:s', $startDate);
        $score->duration_in_seconds = strtotime($score->expire_time) - strtotime($score->start_time);
        $score->level_id = $i;
//            $score->not_used_keys = strtotime($score->keys_count) - strtotime($score->used_keys);
        $score->save();
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
        $end_time = $examEnds == 0 ? date('Y-m-d H:i:s', $startDate + $defaultQuestionTime) : null;
        $answer_time = $examEnds == 0 ? date('Y-m-d H:i:s', $startDate + $userAnswerInSeconds) : null;

        ExamQuestion::factory()->create([
            'exam_id' => $exam->id,
            'question_id' => $question->id,
            'answer' => $examEnds == 0 ? $answer : null,
            'is_correct' => $examEnds == 0 ? $is_correct : 0,
            'key_usage' => $key_usage,
            'start_time' => $start_time,
            'end_time' => $end_time,
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
