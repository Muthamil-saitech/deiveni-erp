<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePropertyRequest extends FormRequest
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
        $merges = [];
        if ($this->has('property_name')) {
            $merges['property_name'] = trim((string) $this->input('property_name'));
        }
        if ($this->has('property_name_ta')) {
            $merges['property_name_ta'] = trim((string) $this->input('property_name_ta')) ?: null;
        }
        if (! empty($merges)) {
            $this->merge($merges);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $propertyId = $this->route('id') ?? $this->route('property') ?? $this->input('id');

        return [
            'property_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('properties', 'property_name')->ignore($propertyId),
            ],
            'property_name_ta' => ['nullable', 'string', 'max:255'],
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
            'property_name.required' => 'Property name is required (சொத்தின் பெயர் அவசியம்).',
            'property_name.string' => 'Property name must be a valid string.',
            'property_name.max' => 'Property name cannot exceed 255 characters.',
            'property_name.unique' => 'This property name is already registered (இந்த சொத்து பெயர் ஏற்கனவே பதிவு செய்யப்பட்டுள்ளது).',
            'property_name_ta.string' => 'Property name in Tamil must be a valid string.',
            'property_name_ta.max' => 'Property name in Tamil cannot exceed 255 characters.',
            'status.required' => 'Please select a status (நிலை தேர்ந்தெடுக்கவும்).',
            'status.in' => 'Selected status must be Active or Inactive.',
        ];
    }
}
