<?php

namespace App\Http\Controllers\API\V1\User;

use App\Enums\ExamStatusEnum;
use App\Enums\IsCorrectEnum;
use App\Http\Controllers\API\V1\BaseController;
use App\Models\Exam;
use App\Models\KeyWord;
use App\Models\Level;
use App\Models\Question;
use App\Models\ExamQuestion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ExamController extends BaseController
{
    protected $questionDuration = 60;
    protected $extraTime = 30;
    protected $currentTime;

    public function __construct() {
        $this->currentTime = Carbon::now()->setTimezone('Asia/Tashkent')->format('Y-m-d H:i:s');
    }

    public function startExam(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'theme_id' => 'required|numeric|exists:themes,id',
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        $user_id = Auth::id();

        if (Exam::where('theme_id', $request->theme_id)
            ->where('expire_time', '>', $this->currentTime)
            ->exists())
            return $this->sendError('Your exam is not completed yet!', 422);

        $exam = Exam::create([
            'user_id' => $user_id,
            'theme_id' => $request->theme_id,
            'start_time' => $this->currentTime,
        ]);

        $this->setExamQuestions($exam);

        return $this->sendResponse($exam->load('questions', 'theme'), 'Exam is started!');
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
        $exam = Exam::where('user_id', $user_id)
            ->where('id', $request->exam_id)
            ->first();

        if (!$exam)
            return $this->sendError('Exam not found!', null, 422);

        if ($exam->expire_time <= $this->currentTime) {
            $this->calcExamResult($exam);
            return $this->sendError('Exam allready completed!', null, 403);
        }

        $question = Question::find($request->question_id);
        $examQuestion = ExamQuestion::where('exam_id', $request->exam_id)
            ->where('question_id', $request->question_id)
            ->first();

        if ($examQuestion->expire_time <= $this->currentTime) {
            $this->calcExamResult($exam);
            return $this->sendError('Exam allready completed!', null, 403);
        }

        $examQuestion->answer = $request->answer;
        $examQuestion->is_correct = ($question->correct == $request->answer) ? 1 : 0;

        if (!$examQuestion->save())
            return $this->sendError('Unable to record your answer!', null, 403);

        return $this->sendResponse([
            'exam_id' => $request->exam_id,
            'question_id' => $request->question_id,
            'answer' => $request->answer,
        ], 'Your answer!');
    }

    public function completeExam($exam_id)
    {
        $exam = Exam::with('questions', 'theme')->where('status', ExamStatusEnum::ACTIVE)
            ->where('user_id', Auth::id())
            ->find($exam_id);
        if(!$exam)
            return $this->sendError('Exam is not found!');

        $examResult = $this->calcExamResult($exam);

        return $this->sendResponse($examResult, 'Exam is completed');

    }

    public function getUserExams()
    {
        if (Exam::where('user_id', Auth::id())->exists()) {
            $query = Exam::where('user_id', Auth::id())->with('theme')->paginate(10);

            $exams = $query->getCollection()->transform(function ($exam) {
                $exam->status = ($exam->expire_time <= $this->currentTime) ? 'active': 'completed';
                return $exam;
            });

            return $this->sendResponse($exams);
        }

        return $this->sendError('Exam is not found!');
    }

    public function getExamResult($exam_id)
    {
        $exam = Exam::where('user_id', Auth::id())->find($exam_id);

        if ($exam)
            return $this->sendError('Exam is not found!');

        if($exam->expire_time >= $this->currentTime)
            return $this->sendError('Exam is not completed yet', null, 403);

        $exam->load('questionsWithCorrect', 'theme');

        return $this->sendResponse($exam, 'Exam is completed');
    }

    public function getExamQuestions($exam_id) {
        $exam = Exam::with('questions', 'theme')->where('status', ExamStatusEnum::ACTIVE)
            ->where('user_id', Auth::id())
            ->find($exam_id);
        if(!$exam)
            return $this->sendError('Exam is not found!');

        return $this->sendResponse($exam);

    }
}
