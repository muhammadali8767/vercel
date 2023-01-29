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

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function questionsWithoutCorrect()
    {
        return $this->belongsToMany(Question::class, 'user_answers', 'exam_id', 'question_id')
        ->select('questions.id', 'question', 'a', 'b', 'c', 'd', 'answer')
        ;
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'user_answers', 'exam_id', 'question_id')
        // ->select('questions.id', 'question', 'a', 'b', 'c', 'd', 'correct', 'answer')
        // ->withPiwot('is_correct')
        ;
    }
}
