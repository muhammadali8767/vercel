<?php

namespace App\Http\Repositories;

use App\Enums\ExamStatusEnum;
use App\Enums\IsCorrectEnum;
use App\Models\Exam;
use App\Models\ExamQuestion;
use Illuminate\Support\Facades\Auth;

class ExamRepository implements BaseRepository
{
    public function getActiveExamById($exam_id) {
        $exam = Exam::with('questions', 'theme')
            ->where('status', ExamStatusEnum::ACTIVE)
            ->where('user_id', Auth::id())
            ->find($exam_id);
        return $exam;
    }
    public function getActiveExams() {
        $exam = Exam::with('questionsForChecking')
            ->where('status', ExamStatusEnum::ACTIVE)
            ->orderBy('id')
            ->select('id', 'status', 'start_time', 'expire_time')
            ->get();
        return $exam;
    }

    public function getNextQuestion($exam_id) {
        return Exam::where('user_id', Auth::id())
            ->with('next_question')
            ->find($exam_id);
    }
    public function getUserExamWithQuestion($exam_id,$question_id) {
        return Exam::where('user_id', Auth::id())
            ->with('questions')
//            ->with(['questions' => function ($q) { $q->select('tender_id', 'file', 'type');}])
            ->where('id', $exam_id)
            ->first();

    }
    public function getUserExams($currentTime) {
        return Exam::where('user_id', Auth::id())
            ->with('questions', 'theme', 'level')
            ->paginate(10)
            ->getCollection()
            ->transform(function ($exam) use ($currentTime) {
                $exam->status = ($exam->expire_time >= $currentTime) ? 'active': 'completed';
                return $exam;
            })
            ;

    }
    public function getExamResult($exam_id) {
        return Exam::where('status', ExamStatusEnum::COMPLETED)
            ->with('questionsWithCorrect')
            ->find($exam_id);
    }
    public function getCorrectAnswers($exam_id) {
        return ExamQuestion::where('exam_id', $exam_id)
            ->where('is_correct', IsCorrectEnum::CORRECT)
            ->count();
    }
}
