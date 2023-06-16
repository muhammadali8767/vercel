<?php

namespace App\Http\Controllers\API\V1\User;

use App\Http\Controllers\API\V1\BaseController;
use App\Models\Theme;

class ThemeController extends BaseController
{
    public function getThemes()
    {
        $themes = Theme::paginate(10);

        return $this->sendResponse($themes);
    }
}
