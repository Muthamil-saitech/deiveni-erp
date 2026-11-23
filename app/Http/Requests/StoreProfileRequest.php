<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => trim((string) $this->name),
            ]);
        }

        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(trim((string) $this->email)),
            ]);
        }

        if ($this->has('phone')) {
            $phone = trim((string) $this->phone);
            $this->merge([
                'phone' => $phone !== '' ? $phone : null,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'phone' => ['nullable', 'string', 'max:25', 'regex:/^[0-9+\-\s()]{7,25}$/'],
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
            'name.required' => 'Full name is required (முழு பெயர் அவசியம்).',
            'name.string' => 'Full name must be a valid text (முழு பெயர் சரியான எழுத்தாக இருக்க வேண்டும்).',
            'name.max' => 'Full name cannot exceed 255 characters (முழு பெயர் 255 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'email.required' => 'Email address is required (மின்னஞ்சல் முகவரி அவசியம்).',
            'email.string' => 'Email address must be a valid text (மின்னஞ்சல் முகவரி சரியான எழுத்தாக இருக்க வேண்டும்).',
            'email.email' => 'Please enter a valid email address (சரியான மின்னஞ்சல் முகவரி பதிவிடவும்).',
            'email.unique' => 'This email address is already registered to another account (இந்த மின்னஞ்சல் ஏற்கனவே பயன்பாட்டில் உள்ளது).',
            'email.max' => 'Email address cannot exceed 255 characters (மின்னஞ்சல் முகவரி 255 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'phone.regex' => 'Please enter a valid phone number (சரியான தொலைபேசி எண் பதிவிடவும்).',
            'phone.max' => 'Phone number cannot exceed 25 characters (தொலைபேசி எண் 25 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
        ];
    }
}
