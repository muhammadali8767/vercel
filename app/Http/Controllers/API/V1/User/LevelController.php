<?php

namespace App\Http\Controllers\API\V1\User;

use App\Http\Controllers\API\V1\BaseController;
use App\Models\Level;

class LevelController extends BaseController
{
    public function getLevels()
    {
        $levels = Level::paginate(10);

        return $this->sendResponse($levels);
    }
}
