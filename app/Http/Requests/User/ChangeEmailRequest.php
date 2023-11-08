<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class ChangeEmailRequest extends FormRequest
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
            'previous_email' => 'required|email|exists:users,email',
            'password' => 'required|string'
        ];
    }

    public function messages()
    {
        return [
            'previous_email.required' => 'The previous email is required.',
            'previous_email.email' => 'The email does not comply with the email format.',
            'previous_email.exists' => 'The previous email does not exists.',
            'password.required' => 'The password is required.',
            'password.string' => 'The password must be a string.'
        ];
    }
}
