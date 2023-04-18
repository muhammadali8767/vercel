<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Models\Level;
use Illuminate\Http\Request;
use App\Http\Controllers\API\V1\BaseController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LevelController extends BaseController
{
    public function index()
    {
        $levels = Level::paginate(10);
        return $this->sendResponse($levels, 'Levels fetched.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:5',
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }
        $level = Level::create($validator->validated());
        return $this->sendResponse($level, 'Level created.');
    }

    public function show($id)
    {
        $level = Level::find($id);
        if (is_null($level)) {
            return $this->sendError('Level does not exist.');
        }
        return $this->sendResponse($level, 'Level fetched.');
    }

    public function update(Request $request, Level $level)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'name' => 'required|string|min:5',
        ]);
        if($validator->fails()){
            return $this->sendError($validator->errors());
        }
        $level->update($validator->validated());
        $level->save();

        return $this->sendResponse($level, 'Level updated.');
    }

    public function destroy(Level $level)
    {
        $level->delete();
        return $this->sendResponse([], 'Level deleted.');
    }
}
