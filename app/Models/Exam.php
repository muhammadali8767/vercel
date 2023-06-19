<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Exam extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'exam_questions', 'exam_id', 'question_id')
            ->with('keyWords')
//            ->select('questions.id', 'question', 'a', 'b', 'c', 'd', 'answer', 'is_correct', 'key_usage', 'start_time', 'expire_time', 'answer_time', 'has_image', 'image')
            ->orderBy('id')
        ;
    }

    public function questionsWithCorrect()
    {
        return $this->belongsToMany(Question::class, 'exam_questions', 'exam_id', 'question_id')
            ->with('keyWords')
//            ->select('questions.id', 'question', 'a', 'b', 'c', 'd', 'correct', 'answer', 'is_correct', 'key_usage', 'start_time', 'expire_time', 'answer_time', 'has_image', 'image')
            ->orderBy('id')
        ;
    }
}
