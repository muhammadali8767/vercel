<?php

namespace App\Http\Repositories;

use App\Enums\ExamStatusEnum;
use App\Models\Exam;
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

    public function getUserExamWithQuestion($exam_id,$question_id) {
        return Exam::where('user_id', Auth::id())
            ->with(['question' => function ($q) { $q->select('tender_id', 'file', 'type');}])
            ->where('id', $exam_id)
            ->first();

    }
}
