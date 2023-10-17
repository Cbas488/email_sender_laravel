<?php

namespace App\Http\Requests;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => 'email|required|unique:users|max:50|min:10',
            'password' => 'required|string|min:8|max:60',
            'confirm_password' => 'required|same:password',
            'name' => 'required|string|max:100|min:1'
        ];
    }

    public function messages()
    {
        return [
            'email.email' => 'The email does not comply with the email format.',
            'email.required' => 'The email is required.',
            'email.unique' => 'The entered email is already registered.',
            'email.max' => 'The email must be a maximum of 50 characters.',
            'email.min' => 'The email must be a maximum of 10 characters.',
            'password.required' => 'The password is required.',
            'password.string' => 'The password must be a string.',
            'password.min' => 'The password must have at least 8 character.',
            'password.max' => 'The password be a maximum of 60 characters.',
            'confirm_password.same' => 'The passwords do not match.',
            'name.required' => 'The name is required.',
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
