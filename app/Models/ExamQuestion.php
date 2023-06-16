<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamQuestion extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function getIsCrrectAttribute() {
        return $this->answer == $this->question->correct;
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
