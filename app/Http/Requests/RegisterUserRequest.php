<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Set to true if authorization is not required
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'card_holder_id' => 'sometimes|integer',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'email_verification' => 'nullable|boolean',
            'email_verification_code' => 'nullable',
            'email_verification_code_expires_at' => 'nullable',
            'sms_verification_code' => 'nullable',
            'sms_verification_code_expires_at' => 'nullable',
        ];
    }
}
