<?php

namespace App\Http\Controllers\API\V1\User;

use App\Http\Controllers\API\V1\BaseController;
use App\Models\Exam;
use App\Models\Score;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ScoreController extends BaseController
{
    public function index()
    {

        $scores = Score::with('user')
            ->withCount('exams')
            ->where('user_id', Auth::id())
            ->orderBy('correct_answers', 'desc')
            ->orderBy('keys_count', 'desc')
            ->orderBy('not_used_keys', 'desc')
            ->orderBy('level_id', 'desc')
            ->paginate(10);

        return $this->sendResponse($scores);
    }

    public function show(Score $score)
    {
        if ($score->user_id != Auth::id())
            return $this->sendError('You dont have permissions', 403);

        $score->load('examsWithQuestions');

        return $this->sendResponse($score);
    }

    public function startExam(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'theme_id' => 'required|numeric|exists:themes,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $user_id = Auth::id();
        dd($user_id);

        $score = Score::where('user_id', $user_id)
            ->where('status', 'active')
            ->where('theme_id', $request->theme_id)
            ->first();

        dd($score);
        if ($score)
            return $this->sendResponse($score);
        $startTime = Carbon::now();

        $score = Score::create([
            'user_id' => $user_id,
            'theme_id' => $request->theme_id,
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'expire_time' => $startTime->addMinutes(15)->format('Y-m-d H:i:s'),
        ]);
    }

    private function getOrCreateScoreExam(Score $score, $level_id)
    {
        if (Exam::where('score_id', $score->id)->where('level_id', $level_id)->where('status', 'active')->exists())
            return Exam::where('score_id', $score->id)
                ->where('level_id', $level_id)
                ->where('status', 'active')
                ->with('questions')
                ->first();


    }


}
