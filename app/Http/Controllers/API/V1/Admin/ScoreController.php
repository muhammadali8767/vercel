<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\API\V1\BaseController;
use App\Models\Score;

class ScoreController extends BaseController
{
    public function index () {

        $scores = Score::with('user')
            ->withCount('exams')
            ->orderBy('correct_answers', 'desc')
            ->orderBy('keys_count', 'desc')
            ->orderBy('not_used_keys', 'desc')
            ->orderBy('level_id', 'desc')
            ->paginate(10);

        return $this->sendResponse($scores);
    }

    public function show (Score $score) {

        $score->load('examsWithQuestions');

        return $this->sendResponse($score);
    }
}
