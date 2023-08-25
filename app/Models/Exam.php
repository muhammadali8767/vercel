<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Exam extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function theme()
    {
        return $this->belongsTo(Theme::class);
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'exam_questions', 'exam_id', 'question_id')
            ->select('questions.id', 'question', 'a', 'b', 'c', 'd', 'answer', 'key_usage', 'start_time', 'expire_time', 'answer_time', 'has_image', 'image')
            ->orderBy('exam_questions.id')
        ;
    }
    public function first_question() {
        return $this->questions()->take(1);
    }
    public function next_question() {
        return $this->questions()->whereNull('answer')->take(1);
    }

    public function questionsForChecking()
    {
        return $this->belongsToMany(Question::class, 'exam_questions', 'exam_id', 'question_id')
            ->select('questions.id', 'answer', 'key_usage', 'start_time', 'expire_time', 'answer_time')
            ->orderBy('exam_questions.id')
        ;
    }

    public function questionsWithCorrect()
    {
        return $this->belongsToMany(Question::class, 'exam_questions', 'exam_id', 'question_id')
            ->select('questions.id', 'question', 'a', 'b', 'c', 'd', 'correct', 'answer', 'is_correct', 'key_usage', 'start_time', 'expire_time', 'answer_time', 'has_image', 'image')
            ->orderBy('exam_questions.id')
        ;
    }
}
