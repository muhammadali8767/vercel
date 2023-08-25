<?php

namespace App\Console\Commands;

use App\Http\Repositories\ExamRepository;
use App\Http\Services\ExamService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CompleteExamCommand extends Command
{
    protected $signature = 'exam:complete-expired';
    protected $description = 'Command description';
    protected $examService;
    public function __construct(ExamService $examService)
    {
        parent::__construct();
        $this->examService = $examService;
    }

    public function handle()
    {
        $activeExams = $this->examService->getActiveExams();
        foreach ($activeExams as $exam) {
            foreach ($exam->questionsForChecking as $question) {
                dump("QUESTION: $question->id | ANSWER: ". !$question->answer . " | START_TIME: $question->start_time | EXPIRE_TIME: $question->expire_time | EXPIRED: " . strval($question->expire_time >= now()->format('Y-m-d HH:i:s')));
//                dump("QUESTION: $question->id | ANSWER: ". !$question->answer . " | START_TIME: $question->start_time | EXPIRE_TIME: $question->expire_time | EXPIRED: ". $question->expire_time >= now()->format('Y-m-d HH:i:s'));
//                if (
//                    !$question->answer
//                    && $question->expire_time
//                    && $question->expire_time < now()->format('Y-m-d HH:i:s')
//                ) {
//                    dump($this->examService->completeExam($exam));
//                }
            }
        }

        return Command::SUCCESS;
    }
}
