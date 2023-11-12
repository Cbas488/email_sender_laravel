<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Exceptions\HttpResponseException;

class EnableAccountUser extends FormRequest
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
            'email' => 'required|email',
            'password' =>  'required',
            'confirm_password' => 'required|same:password'
        ];
    }

    public function messages() {
        return [
            'email.required' => 'The email is required.',
            'email.email' => 'The email does not comply with the email format.',
            'password.required' => 'The password is required.',
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
