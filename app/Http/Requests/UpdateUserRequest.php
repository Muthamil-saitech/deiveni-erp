<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('id') ?? $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => ['required', 'string', 'max:30'],
            'role_id' => ['required', 'exists:roles,id'],
            'status' => ['required', 'in:Active,Inactive'],
            'password' => ['nullable', 'string', 'min:6'],
        ];
    }

    /**
     * Get custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Full name is required (பயனாளர் பெயர் தேவை).',
            'email.required' => 'Email address is required (மின்னஞ்சல் தேவை).',
            'email.unique' => 'Email is already taken (இந்த மின்னஞ்சல் ஏற்கனவே பயன்பாட்டில் உள்ளது).',
            'phone.required' => 'Phone number is required (தொலைபேசி எண் தேவை).',
            'role_id.required' => 'Please select a role (பங்கு தேர்ந்தெடுக்கப்பட வேண்டும்).',
            'role_id.exists' => 'Selected role does not exist (தேர்ந்தெடுக்கப்பட்ட பங்கு இல்லை).',
        ];
    }
}
