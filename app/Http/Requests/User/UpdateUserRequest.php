<?php

namespace App\Http\Requests\User;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    function prepareForValidation()
    {
        if(!is_numeric($this -> route('id'))){
            throw new HttpResponseException(
                ApiResponse::fail(
                    'There are errors in the validation.',
                    JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                    ['The ID must be numeric.']
                )
            );
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['email', 'nullable', 'max:50', 'min:10', Rule::unique('users', 'email') -> ignore($this -> route('id'))],
            'name' => 'nullable|string|max:100|min:1'
        ];
    }

    public function messages()
    {
        return [
            'email.email' => 'The email does not comply with the email format.',
            'email.unique' => 'The entered email is already registered.',
            'email.max' => 'The email must be a minimum of 50 characters.',
            'email.min' => 'The email must be a maximum of 10 characters.',
            'name.string' => 'The name must be a string.',
            'name.max' => 'The name must be a maximum of 100 characters.',
            'name.min' => 'The name must have at least 1 character.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {   
        throw new HttpResponseException(
            ApiResponse::fail(
                'There are errors in the validation.',
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                $validator -> errors() -> toArray()
            )
        );
    }
}
