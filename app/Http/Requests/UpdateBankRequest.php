<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBankRequest extends FormRequest
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
        if ($this->has('bank_name')) {
            $this->merge([
                'bank_name' => trim((string) $this->input('bank_name')),
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
        $bankId = $this->route('id') ?? $this->route('bank') ?? $this->input('id');

        return [
            'bank_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('banks', 'bank_name')->ignore($bankId),
            ],
            'bank_name_ta' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:Active,Inactive'],
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
            'bank_name.required' => 'Bank name is required (வங்கி பெயர் அவசியம்).',
            'bank_name.string' => 'Bank name must be a valid string.',
            'bank_name.max' => 'Bank name cannot exceed 255 characters.',
            'bank_name.unique' => 'This bank name is already registered (இந்த வங்கி பெயர் ஏற்கனவே பதிவு செய்யப்பட்டுள்ளது).',
            'bank_name_ta.max' => 'Tamil name cannot exceed 255 characters.',
            'status.required' => 'Please select a status (நிலை தேர்ந்தெடுக்கவும்).',
            'status.in' => 'Selected status must be Active or Inactive.',
        ];
    }
}
