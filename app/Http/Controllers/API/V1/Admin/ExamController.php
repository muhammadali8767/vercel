<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\API\V1\BaseController;
use App\Models\Exam;

class ExamController extends BaseController
{
    public function index () {

        $exams = Exam::with('user')
            ->orderBy('correct_answers', 'desc')
            ->orderBy('keys_count', 'desc')
            ->orderBy('not_used_keys', 'desc')
            ->orderBy('level_id', 'desc')
            ->paginate(10);

        return $this->sendResponse($exams);
    }

    public function show (Exam $exam) {

        // $score->load('examsWithQuestions');

        return $this->sendResponse($exam);
    }
}
