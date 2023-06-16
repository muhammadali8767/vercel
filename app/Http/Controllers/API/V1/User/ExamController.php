<?php

namespace App\Http\Controllers\API\V1\User;

use App\Enums\ExamStatusEnum;
use App\Enums\IsCorrectEnum;
use App\Http\Controllers\API\V1\BaseController;
use App\Models\Exam;
use App\Models\Question;
use App\Models\ExamQuestion;
use App\Models\Score;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ExamController extends BaseController
{

    public function startExam(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'theme_id' => 'required|numeric|exists:themes,id',
            'level_id' => 'required|numeric|exists:levels,id',
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }
        dd($request->all());

        $user_id = Auth::id();
        $now = Carbon::now()->setTimezone('Asia/Tashkent');

        $score = Score::with('questions', 'theme', 'level')
            ->where('user_id', $user_id)
            ->where('theme_id', $request->theme_id)
            ->where('level_id', $request->level_id)
            ->where('expire_time', '>', $now->format('Y-m-d H:i:s'))
            ->first();

        if ($score) {
            return $this->sendResponse($exam, 'Your exam is not completed yet!');
        } else {

            $exam = Exam::create([
                'user_id' => $user_id,
                'theme_id' => $request->theme_id,
                'level_id' => $request->level_id,
                'start_time' => $now->format('Y-m-d H:i:s'),
                'expire_time' => $now->addMinutes(30)->format('Y-m-d H:i:s'),
            ]);

            $questions = Question::where('theme_id', $request->theme_id)
                ->where('level_id', $request->level_id)
                ->inRandomOrder()
                ->limit(10)
                ->get();

            foreach ($questions as $question) {
                ExamQuestion::create([
                    'exam_id' => $exam->id,
                    'question_id' => $question->id,
                ]);
            }

            return $this->sendResponse($exam->load('questions', 'theme', 'level'), 'Exam is started!');
        }
    }

    public function startExamOld(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'theme_id' => 'required|numeric|exists:themes,id',
            'level_id' => 'required|numeric|exists:levels,id',
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        $user_id = Auth::id();
        $now = Carbon::now()->setTimezone('Asia/Tashkent');

        $exam = Exam::with('questions', 'theme', 'level')
            ->where('user_id', $user_id)
            ->where('theme_id', $request->theme_id)
            ->where('level_id', $request->level_id)
            ->where('expire_time', '>', $now->format('Y-m-d H:i:s'))
            ->first();

        if ($exam) {
            return $this->sendResponse($exam, 'Your exam is not completed yet!');
        } else {

            $exam = Exam::create([
                'user_id' => $user_id,
                'theme_id' => $request->theme_id,
                'level_id' => $request->level_id,
                'start_time' => $now->format('Y-m-d H:i:s'),
                'expire_time' => $now->addMinutes(30)->format('Y-m-d H:i:s'),
            ]);

            $questions = Question::where('theme_id', $request->theme_id)
                ->where('level_id', $request->level_id)
                ->inRandomOrder()
                ->limit(10)
                ->get();

            foreach ($questions as $question) {
                ExamQuestion::create([
                    'exam_id' => $exam->id,
                    'question_id' => $question->id,
                ]);
            }

            return $this->sendResponse($exam->load('questions', 'theme', 'level'), 'Exam is started!');
        }
    }

    public function setAnswer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exam_id' => 'required|numeric|exists:exams,id',
            'question_id' => 'required|numeric|exists:questions,id',
            'answer' => [
                'required','string', Rule::in(['a', 'b', 'c', 'd']),
            ],
        ]);
        if($validator->fails()){
            return $this->sendError($validator->errors());
        }
        $user_id = Auth::id();

        $now = Carbon::now()->setTimezone('Asia/Tashkent')->format('Y-m-d H:i:s');

        if (Exam::where('expire_time', '>', $now)
                ->where('user_id', $user_id)
                ->where('id', $request->exam_id)
                ->exists()
            ) {
            $question = Question::find($request->question_id);
            $userAnswer = ExamQuestion::where('exam_id', $request->exam_id)
            ->where('question_id', $request->question_id)->first();
            $userAnswer->answer = $request->answer;
            $userAnswer->is_correct = ($question->correct == $request->answer) ? 1 : 0;

            if ($userAnswer->save()) {
                return $this->sendResponse([
                    'exam_id' => $request->exam_id,
                    'question_id' => $request->question_id,
                    'answer' => $request->answer,
                ], 'Your answer!');
            }
            return $this->sendError('Unable to record your answer!', null, 403);

        }

        return $this->sendError('Exam allready completed!', null, 403);
    }

    public function completeExam($exam_id)
    {
        $now = Carbon::now()->setTimezone('Asia/Tashkent')->format('Y-m-d H:i:s');
        $exam = Exam::with('questions', 'theme', 'level')->where('status', ExamStatusEnum::ACTIVE)
            ->where('user_id', Auth::id())
            ->find($exam_id);
        if($exam) {
            $exam->status = ExamStatusEnum::COMPLETED;
            $exam->expire_time = $now;
            $exam->correct_answers = ExamQuestion::where('exam_id', $exam_id)->where('is_correct', IsCorrectEnum::CORRECT)->count();
            $exam->save();

            return $this->sendResponse($exam, 'Exam is completed');
        }

        return $this->sendError('Exam is not found!');
    }

    public function getUserExams()
    {
        if (Exam::where('user_id', Auth::id())->exists()) {
            $query = Exam::where('user_id', Auth::id())->with('theme', 'level')->paginate(10);
            $now = Carbon::now()->setTimezone('Asia/Tashkent')->format('Y-m-d H:i:s');

            $exams = $query->getCollection()->transform(function ($exam, $key) use ($now) {
                $exam->status = ($exam->expire_time > $now) ? 'active': 'completed';
                return $exam;
            });

            return $this->sendResponse($exams);
        }

        return $this->sendError('Exam is not found!');
    }

    public function getExamResult($exam_id)
    {
        // ->with(['buyer' => function ($query){
        //     $query->select('name');
        // }])


        $now = Carbon::now()->setTimezone('Asia/Tashkent')->format('Y-m-d H:i:s');
        $exam = Exam::with('questions', 'theme', 'level')->where('user_id', Auth::id())->find($exam_id);

        if ($exam) {
            if($exam->expire_time > $now) {
                return $this->sendError('Exam is not completed yet', null, 403);
            }
            $exam->status = 'completed';
            $exam->correct_answer_count=0;
            foreach ($exam->questions as $question) {
                if ($question->correct == $question->answer) {
                    $exam->correct_answer_count++;
                }
            }

            return $this->sendResponse($exam, 'Exam is completed');
        }

        return $this->sendError('Exam is not found!');
    }
}
