<?php

namespace App\Http\Controllers\API\V1\User;

use App\Enums\ExamStatusEnum;
use App\Enums\IsCorrectEnum;
use App\Http\Controllers\API\V1\BaseController;
use App\Http\Requests\User\Exam\SetAnswerRequest;
use App\Http\Requests\User\Exam\StartExamRequest;
use App\Http\Services\ExamService;
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
    protected $currentTime;
    protected $examService;

    public function __construct(ExamService $examService) {
        $this->currentTime = Carbon::now()->setTimezone('Asia/Tashkent')->format('Y-m-d H:i:s');
        $this->examService = $examService;
    }

    public function startExam(StartExamRequest $request)
    {
        if ($this->examService->checkUserActiveExamExists())
            return $this->sendError('Your exam is not completed yet!', 403);

        $exam = $this->examService->createExam($request->theme_id);
        $this->examService->setExamQuestions($exam);
        $exam->load('first_question', 'theme')->loadCount('questions');

        return $this->sendResponse($exam, 'Exam is started!');
    }

    public function setAnswer(SetAnswerRequest $request)
    {
        if (!$exam = $this->examService->getUserExamWithQuestion($request->exam_id, $request->question_id))
            return $this->sendError('Exam not found!', null, 422);

        if ($exam->expire_time && $exam->expire_time >= $this->currentTime) {
//            $this->examService->completeExam($exam);
            return $this->sendError('Exam allready completed!', null, 403);
        }

        $question = Question::find($request->question_id);
        $examQuestion = ExamQuestion::where('exam_id', $request->exam_id)
            ->where('question_id', $request->question_id)
            ->first();

        if ($examQuestion->expire_time <= $this->currentTime) {
//            $this->examService->completeExam($exam);
            return $this->sendError('Exam allready completed!', null, 403);
        }

        $examQuestion->answer = $request->answer;
        $examQuestion->is_correct = ($question->correct == $request->answer) ? 1 : 0;
        $examQuestion->expire_time = $this->currentTime;
        $examQuestion->answer_time = $this->currentTime;

        if (!$examQuestion->save())
            return $this->sendError('Unable to record your answer!', null, 403);

        $nextQuestion = ExamQuestion::where('exam_id', $request->exam_id)
            ->whereNull('answer')
            ->with('question', 'level', 'keyUsage')
            ->select('id', 'exam_id', 'level_id', 'question_id', 'key_usage', 'start_time', 'expire_time', )
            ->orderBy('id')
            ->first();

        if (!$nextQuestion) {
            $this->examService->completeExam($exam);
            return $this->sendResponse([
                'exam_id' => $request->exam_id,
                'question_id' => $request->question_id,
                'answer' => $request->answer,
                'next_question' => $nextQuestion,
                'exam_status' => $exam->status,
            ], 'Your answer!');
        }

        $nextQuestion->start_time = $this->currentTime;
        $nextQuestion->expire_time = Carbon::parse($this->currentTime)
            ->addSeconds(env('QUESTION_DURATION'))
            ->format('Y-m-d H:i:s');
        $nextQuestion->save();
        return $this->sendResponse([
            'exam_id' => $request->exam_id,
            'question_id' => $request->question_id,
            'answer' => $request->answer,
            'next_question' => $nextQuestion,
            'exam_status' => $exam->status,
        ], 'Your answer!');
    }

    public function getNextQuestion($exam_id) {
//        if (!$nextQuestion)
//            return $this->sendError('Question not found', 403);
//        return $this->sendResponse($nextQuestion);
    }
    public function completeExam($exam_id)
    {
        $exam = Exam::with('questions', 'theme')
            ->where('status', ExamStatusEnum::ACTIVE)
            ->where('user_id', Auth::id())
            ->find($exam_id);
        if(!$exam)
            return $this->sendError('Exam is not found!');

        $examResult = $this->examService->completeExam($exam);

        return $this->sendResponse($examResult, 'Exam is completed');

    }

    public function getUserExams()
    {
        $exams = $this->examService->getUserExams();
        return $this->sendResponse($exams);
    }

    public function getExamResult($exam_id)
    {
        if (!$exam = $this->examService->getExamResult($exam_id))
            return $this->sendError('Exam is not found!');

        return $this->sendResponse($exam, 'Exam is completed');
    }

//    public function getExamQuestions($exam_id) {
//        $exam = Exam::with('questions', 'theme')
//            ->where('status', ExamStatusEnum::ACTIVE)
//            ->where('user_id', Auth::id())
//            ->find($exam_id);
//        if(!$exam)
//            return $this->sendError('Exam is not found!');
//
//        return $this->sendResponse($exam);
//
//    }
}
