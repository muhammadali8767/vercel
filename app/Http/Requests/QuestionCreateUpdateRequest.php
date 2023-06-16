<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class QuestionCreateUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'theme_id' => 'required|exists:themes,id',
            'level_id' => 'required|exists:levels,id',
            'question' => 'required|string|min:5',
            'hint_for_the_question' => 'required|string|min:5',
            'has_image' => 'required|boolean',
            'image' => ['required_if:has_image,1', File::image()->min(10)->max(12 * 1024)],
            'a' => 'required|string|min:1',
            'b' => 'required|string|min:1',
            'c' => 'required|string|min:1',
            'd' => 'required|string|min:1',
            'correct' => [
                'required','string', Rule::in(['a', 'b', 'c', 'd']),
            ],
            'keyWords' => 'required|array',
            'keyWords.*' => 'exists:key_words,id',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }



}
