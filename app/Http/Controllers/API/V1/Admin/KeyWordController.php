<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\API\V1\BaseController;
use App\Models\KeyWord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KeyWordController extends BaseController
{
    public function index()
    {
        $keyWords = KeyWord::paginate(10);
        return $this->sendResponse($keyWords, 'keywords fetched.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key_word' => 'required|string|min:5',
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }
        $keyWord = KeyWord::create($validator->validated());
        return $this->sendResponse($keyWord, 'keyword created.');
    }

    public function show($id)
    {
        $keyWord = KeyWord::find($id);
        if (is_null($keyWord)) {
            return $this->sendError('keyword does not exist.');
        }
        return $this->sendResponse($keyWord, 'KeyWord fetched.');
    }

    public function update(Request $request, KeyWord $keyWord)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'key_word' => 'required|string|min:5',
        ]);
        if($validator->fails()){
            return $this->sendError($validator->errors());
        }
        $keyWord->update($validator->validated());
        $keyWord->save();

        return $this->sendResponse($keyWord, 'keyword updated.');
    }

    public function destroy(KeyWord $keyWord)
    {
        $keyWord->delete();
        return $this->sendResponse([], 'keyword deleted.');
    }
}
