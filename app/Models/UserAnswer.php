<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAnswer extends Model
{
    use HasFactory;
    protected $guarded = [];

    // public function getDays2Attribute()
    // {
    //     $placement_term = Carbon::parse($this->placement_term)
    //         ->setTimezone('Asia/Tashkent')
    //         ->addWeekdays(2)
    //         ->format('Y-m-d H:i:s');

    //     return $placement_term;
    // }



    public function getPravilnoAttribute() {
        return $this->answer == $this->question->correct;
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
