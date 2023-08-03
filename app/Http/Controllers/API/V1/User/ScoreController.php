<?php

namespace App\Http\Controllers\API\V1\User;

use App\Enums\ExamStatusEnum;
use App\Http\Controllers\API\V1\BaseController;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\KeyUsage;
use App\Models\KeyWord;
use App\Models\Level;
use App\Models\Question;
use App\Models\Score;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ScoreController extends BaseController
{
    protected $questionDuration = 60;
    protected $extraTime = 30;
    public function index()
    {
        $scores = Score::with('user','theme')
            ->withCount('exams')
            ->where('user_id', Auth::id())
            ->orderBy('correct_answers', 'desc')
            ->orderBy('keys_count', 'desc')
            ->orderBy('not_used_keys', 'desc')
            ->orderBy('level_id', 'desc')
            ->paginate(10)->transform(function ($item) {
                if ($item->expire_time <= now()->format('Y-m-d H:i:s'))
                    $item->status = ExamStatusEnum::COMPLETED;

                return $item;
            });

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
            ->exists();

        if ($score)
            Score::where('user_id', $user_id)
                ->where('status', 'active')
                ->where('theme_id', $request->theme_id)
                ->update(['status' => ExamStatusEnum::COMPLETED]);

        $startTime = Carbon::now();
        $score = Score::create([
            'user_id' => $user_id,
            'theme_id' => $request->theme_id,
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            // 'expire_time' => $startTime->addMinutes(15)->format('Y-m-d H:i:s'),
        ]);

        $this->createExams($score);

        $score->load('user', 'theme');

        return $this->sendResponse($score);
    }

    public function getQuestionList(Request $request) {
        $validator = Validator::make($request->all(), [
            'score_id' => 'required|numeric|exists:scores,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $scre = Score::with('questions')->find($request->score_id);

        return $this->sendResponse($scre);

    }

    public function getNextQuestion (Request $request) {
        $validator = Validator::make($request->all(), [
            'score_id' => 'required|numeric|exists:scores,id',
            'question_id' => 'sometimes|numeric|exists:questions,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $score = Score::find($request->score_id);

        if ($score->user_id != Auth::id())
            return $this->sendError('Forbidden', 403);

        if (strtotime($score->expire_time) >= now() || $score->status != 'active')
            return $this->sendError('Imtihon yakunlandi', 403);

        $exams = Exam::where('score_id', $score->id)->pluck('id')->toArray();
        $examQuestion = ExamQuestion::whereIn('exam_id', $exams)->with('question')
            // ->where('question_id', $request->question_id)
            // ->whereDate('expire_time', '<=', now())
            ->orderBy('id')
            ->get();

        return $this->sendResponse($examQuestion);

        if ($examQuestion)
            return $this->sendError('Question not found', 422);


    }

   public function setAnswer(Request $request) {
       $validator = Validator::make($request->all(), [
           'score_id' => 'required|numeric|exists:scores,id',
           'question_id' => 'required|numeric|exists:questions,id',
       ]);

       if ($validator->fails()) {
           return $this->sendError($validator->errors());
       }

       $score = Score::find($request->score_id);

       if ($score->user_id != Auth::id())
           return $this->sendError('Forbidden', 403);

       if (strtotime($score->expire_time) >= now() || $score->status != 'active')
           return $this->sendError('Imtihon yakunlandi', 403);

       $exams = Exam::where('score_id', $score->id)->pluck('id')->toArray();
       $examQuestion = ExamQuestion::whereIn('exam_id', $exams)
           ->where('question_id', $request->question_id)
           ->whereDate('expire_time', '<=', now())
           ->first();

       if ($examQuestion)
           return $this->sendError('Question not found', 422);
   }

   private function createExams($score) {
        $levels = Level::all();
        $startDate = strtotime($score->start_time);
        foreach ($levels as $level) {
                $exam = Exam::create([
                    'level_id' => $level->id,
                    'score_id' => $score->id,
                    'start_time' => date('Y-m-d H:i:s', $startDate),
                ]);

            $keyWords = [KeyWord::all()->random()->id];

            $simpleQuestions = Question::where('theme_id', $score->theme_id)
                ->where('level_id', $exam->level_id)
                ->whereHas('keyWords', function ($q) use ($keyWords) {
                    $q->whereIn('key_word_id', $keyWords);
                })
                ->where('has_image', 0)
                ->inRandomOrder()
                ->limit($exam->level->simple_question_count)
                ->get();

            foreach ($simpleQuestions as $question) {
                ExamQuestion::create([
                    'exam_id' => $exam->id,
                    'question_id' => $question->id,
                    'start_time' => date('Y-m-d H:i:s', $startDate),
                    'expire_time' => date('Y-m-d H:i:s', $startDate + $this->questionDuration),
                ]);
                $startDate += $this->questionDuration;
            }

            $hasImageQuestions = Question::where('theme_id', $score->theme_id)
                ->where('level_id', $exam->level_id)
                ->whereHas('keyWords', function ($q) use ($keyWords) {
                    $q->whereIn('key_word_id', $keyWords);
                })
                ->where('has_image', 1)
                ->inRandomOrder()
                ->limit($exam->level->has_image_count)
                ->get();

            foreach ($hasImageQuestions as $question) {
                ExamQuestion::create([
                    'exam_id' => $exam->id,
                    'question_id' => $question->id,
                    'start_time' => date('Y-m-d H:i:s', $startDate),
                    'expire_time' => date('Y-m-d H:i:s', $startDate + $this->questionDuration),
                ]);
                $startDate += $this->questionDuration;
            }
            $exam->expire_time = date('Y-m-d H:i:s', $startDate);
            $exam->save();
        }

        $score->expire_time = date('Y-m-d H:i:s', $startDate);
        $score->save();
    }
}
