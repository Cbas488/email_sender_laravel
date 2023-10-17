<?php

namespace App\Http\Requests;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;

class ChangePasswordUserRequest extends FormRequest
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
            'email' => 'required|email|exists:users,email',
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:8|max:60',
            'confirm_password' => 'required|same:new_password',
        ];
    }

    public function messages()
    {
        return [
            'email.required' => 'The email is required',
            'email.email' => 'The email does not comply with the email format.',
            'email.exists' => 'Email does not exist.',
            'old_password.required' => 'The old password is required.',
            'old_password.string' => 'The old passwors must be string.',
            'new_password.required' => 'The password is required.',
            'new_password.string' => 'The password must be a string.',
            'new_password.min' => 'The password must have at least 8 character.',
            'new_password.max' => 'The password be a maximum of 60 characters.',
            'confirm_password.required' => 'The confirmation password is required.',
            'confirm_password.same' => 'The passwords do not match.',
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
