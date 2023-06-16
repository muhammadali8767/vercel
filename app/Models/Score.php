<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Score extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function user () {
        return $this->belongsTo(User::class)->select('id', 'name');
    }

    public function exams () {
        return $this->hasMany(Exam::class)->orderBy('id');
    }

    public function examsWithCorrectAnswers () {
        return $this->hasMany(Exam::class)->with('questionsWithCorrect')->orderBy('id');
    }

    public function examsWithQuestions () {
        return $this->hasMany(Exam::class)->with('questions')->orderBy('id');
    }
}
