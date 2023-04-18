<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Models\Theme;
use Illuminate\Http\Request;
use App\Http\Controllers\API\V1\BaseController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ThemeController extends BaseController
{
    public function index()
    {
        $themes = Theme::paginate(10);
        return $this->sendResponse($themes, 'Themes fetched.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:5',
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }
        $theme = Theme::create($validator->validated());
        return $this->sendResponse($theme, 'Theme created.');
    }

    public function show($id)
    {
        $theme = Theme::find($id);
        if (is_null($theme)) {
            return $this->sendError('Theme does not exist.');
        }
        return $this->sendResponse($theme, 'Theme fetched.');
    }

    public function update(Request $request, Theme $theme)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:5',
        ]);
        if($validator->fails()){
            return $this->sendError($validator->errors());
        }
        $theme->update($validator->validated());
        $theme->save();

        return $this->sendResponse($theme, 'Theme updated.');
    }

    public function destroy(Theme $theme)
    {
        $theme->delete();
        return $this->sendResponse([], 'Theme deleted.');
    }
}
