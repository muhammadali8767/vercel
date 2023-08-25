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
        return $this->belongsTo(Question::class)->select('id', 'question', 'a','b','c','d','has_image','image');
    }
    public function level()
    {
        return $this->belongsTo(Level::class)->select('id', 'name');
    }
    public function keyUsage()
    {
        return $this->belongsTo(KeyUsage::class, 'key_usage')->select('id', 'usage_type');
    }
}
