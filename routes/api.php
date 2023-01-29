<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\V1\Auth\AuthController;
use App\Http\Controllers\API\V1\Admin\QuestionController;

use App\Http\Controllers\API\V1\User\ExamController;
use App\Http\Controllers\API\V1\User\LevelController;
use App\Http\Controllers\API\V1\User\ThemeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    Route::any('forbidden', [AuthController::class, 'forbidden'])->name('forbidden');

    Route::post('register', [AuthController::class, 'signup']);

    Route::post('login', [AuthController::class, 'signin']);

    Route::middleware(['auth:sanctum'])->group( function () {

        Route::post('logout', [AuthController::class, 'signout']);

        // Admin Routes
        Route::group(['middleware' => ['role:admin'], 'prefix' => 'admin'], function () {

            Route::resource('questions', QuestionController::class)->except('create', 'edit');

            Route::resource('questions', QuestionController::class)->except('create', 'edit');

            Route::resource('questions', QuestionController::class)->except('create', 'edit');

        });

        // User Routes
        Route::group(['middleware' => ['role:user']], function () {

            Route::group(['prefix' => 'theme'], function () {

                Route::get('get-themes', [ThemeController::class, 'getThemes']);

            });

            Route::group(['prefix' => 'level'], function () {

                Route::get('get-levels', [LevelController::class, 'getLevels']);

            });

            Route::group(['prefix' => 'exam'], function () {

                Route::post('start-exam', [ExamController::class, 'startExam']);

                Route::post('set-answer', [ExamController::class, 'setAnswer']);

                Route::post('complete-exam/{exam_id}', [ExamController::class, 'completeExam']);

                Route::get('get-user-exams', [ExamController::class, 'getUserExams']);

                Route::get('get-exam-result/{exam_id}', [ExamController::class, 'getExamResult']);

            });

        });

    });

});
