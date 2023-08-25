<?php

namespace App\Http\Services;

use App\Enums\ExamStatusEnum;
use App\Enums\IsCorrectEnum;
use App\Http\Repositories\ExamRepository;
use App\Http\Repositories\QuestionRepository;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\KeyWord;
use App\Models\Level;
use App\Models\Question;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class QuestionService implements BaseService
{
    private $questionRepository;

    public function __construct (QuestionRepository $questionRepository) {
        $this->questionRepository = $questionRepository;
    }
}
