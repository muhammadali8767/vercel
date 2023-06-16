<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Requests\QuestionCreateUpdateRequest;
use App\Models\QuestionKeyWord;
use App\Traits\Upload;
use Illuminate\Http\Request;
use App\Http\Controllers\API\V1\BaseController;
use App\Models\Question;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class QuestionController extends BaseController
{
    use Upload;
    public function index()
    {
        $questions = Question::paginate(10);
        return $this->sendResponse($questions, 'Questions fetched.');
    }

    public function store(QuestionCreateUpdateRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $path = $this->UploadFile($request->file('image'), 'images');
            $data['image'] = $path;
        }
        $keyWords = $data['keyWords'];
        unset($data['keyWords']);

        if ($question = Question::create($data)) {
            $this->createOrUpdateQuestionKeyWords($question, $keyWords);
            return $this->sendResponse($question->load('keyWords'), 'Question created.');
        }

        return $this->sendError('Question not created.');
    }

    public function show(Question $question)
    {
        $question->load('keyWords');
        return $this->sendResponse($question, 'Question fetched.');
    }

    public function update(QuestionCreateUpdateRequest $request, Question $question)
    {
        if (!is_null($question->image)) {
            $this->deleteFile($question->image);
        }

        $data = $request->validated();

        if ($request->hasFile('image')) {
            $path = $this->UploadFile($request->file('image'), 'images');
            $data['image'] = $path;
        }

        $keyWords = $data['keyWords'];
        unset($data['keyWords']);

        if ($question->update($data)) {
            $this->createOrUpdateQuestionKeyWords($question, $keyWords);
            return $this->sendResponse($question->load('keyWords'), 'Question updated.');
        }

        return $this->sendError('Question not updated.');
    }

    public function destroy(Question $question)
    {
        if (!is_null($question->image)) {
            $this->deleteFile($question->image);
        }

        if (QuestionKeyWord::where('question_id', $question->id)->exists())
            QuestionKeyWord::where('question_id', $question->id)->delete();

        $question->delete();
        return $this->sendResponse([], 'Question deleted.');
    }

    private function createOrUpdateQuestionKeyWords (Question $question, array $keyWords) {
        if (QuestionKeyWord::where('question_id', $question->id)->exists())
            QuestionKeyWord::where('question_id', $question->id)->delete();

        if (is_array($keyWords) && !empty($keyWords))
            foreach ($keyWords as $keyWord)
                QuestionKeyWord::create([
                    'question_id' => $question->id,
                    'key_word_id' => $keyWord
                ]);
    }
}
