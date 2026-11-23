<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            'new_password' => ['required', 'string', 'min:8', 'different:current_password'],
            'new_password_confirmation' => ['required', 'string', 'same:new_password'],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Current password is required (தற்போதைய கடவுச்சொல் அவசியம்).',
            'current_password.string' => 'Current password must be a valid text (தற்போதைய கடவுச்சொல் சரியான எழுத்தாக இருக்க வேண்டும்).',
            'current_password.current_password' => 'The current password provided is incorrect (தற்போதைய கடவுச்சொல் தவறானது).',
            'new_password.required' => 'New password is required (புதிய கடவுச்சொல் அவசியம்).',
            'new_password.string' => 'New password must be a valid text (புதிய கடவுச்சொல் சரியான எழுத்தாக இருக்க வேண்டும்).',
            'new_password.min' => 'New password must be at least 8 characters long (புதிய கடவுச்சொல் குறைந்தது 8 எழுத்துக்கள் இருக்க வேண்டும்).',
            'new_password.different' => 'New password must be different from current password (புதிய கடவுச்சொல் தற்போதைய கடவுச்சொல்லில் இருந்து மாறுபட்டிருக்க வேண்டும்).',
            'new_password_confirmation.required' => 'Please confirm your new password (கடவுச்சொல்லை மீண்டும் உள்ளிடவும்).',
            'new_password_confirmation.string' => 'Password confirmation must be a valid text (கடவுச்சொல் உறுதிப்படுத்தல் சரியான எழுத்தாக இருக்க வேண்டும்).',
            'new_password_confirmation.same' => 'New password and confirmation do not match (கடவுச்சொல் உறுதிப்படுத்தல் பொருந்தவில்லை).',
        ];
    }
}
