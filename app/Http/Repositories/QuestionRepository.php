<?php

namespace App\Http\Repositories;

use App\Enums\ExamStatusEnum;
use App\Enums\IsCorrectEnum;
use App\Models\Exam;
use App\Models\ExamQuestion;
use Illuminate\Support\Facades\Auth;

class QuestionRepository implements BaseRepository
{
    public function __construct()
    {
        parent::__construct();
    }

}
