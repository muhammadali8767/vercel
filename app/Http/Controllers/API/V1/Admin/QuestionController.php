<?php

namespace App\Http\Controllers\API\V1\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\API\V1\BaseController;
use App\Models\Question;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class QuestionController extends BaseController
{
    public function index()
    {
        $questions = Question::paginate(10);
        return $this->sendResponse($questions, 'Questions fetched.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'theme_id' => 'required|themes:levels,id',
            'level_id' => 'required|exists:levels,id',
            'question' => 'required|string|min:5',
            'a' => 'required|string|min:1',
            'a' => 'required|string|min:1',
            'a' => 'required|string|min:1',
            'a' => 'required|string|min:1',
            'current' => [
                'required','string', Rule::in(['a', 'b', 'c', 'd']),
            ],
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }
        $question = Question::create($validator->validated());
        return $this->sendResponse($question, 'Question created.');
    }

    public function show($id)
    {
        $question = Question::find($id);
        if (is_null($question)) {
            return $this->sendError('Question does not exist.');
        }
        return $this->sendResponse($question, 'Question fetched.');
    }

    public function update(Request $request, Question $question)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'theme_id' => 'required|themes:levels,id',
            'level_id' => 'required|exists:levels,id',
            'question' => 'required|string|min:5',
            'a' => 'required|string|min:1',
            'a' => 'required|string|min:1',
            'a' => 'required|string|min:1',
            'a' => 'required|string|min:1',
            'current' => [
                'required','string', Rule::in(['a', 'b', 'c', 'd']),
            ],
        ]);
        if($validator->fails()){
            return $this->sendError($validator->errors());
        }
        $question->update($validator->validated());
        $question->save();

        return $this->sendResponse($question, 'Question updated.');
    }

    public function destroy(Question $question)
    {
        $question->delete();
        return $this->sendResponse([], 'Question deleted.');
    }
}
