<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitRequest extends FormRequest
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
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper(trim((string) $this->code)),
            ]);
        }

        if ($this->has('name_en')) {
            $this->merge(['name_en' => trim((string) $this->name_en)]);
        }

        if ($this->has('name_ta')) {
            $this->merge(['name_ta' => trim((string) $this->name_ta)]);
        }

        if ($this->has('usage')) {
            $this->merge(['usage' => trim((string) $this->usage)]);
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
            'name_en' => ['required', 'string', 'max:255', Rule::unique('units', 'name_en')],
            'name_ta' => ['required', 'string', 'max:255', Rule::unique('units', 'name_ta')],
            'code' => ['required', 'string', 'max:50', Rule::unique('units', 'code')],
            'usage' => ['nullable', 'string', 'max:255'],
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
            'name_en.required' => 'Unit English name is required (அளவீட்டின் ஆங்கில பெயர் அவசியம்).',
            'name_en.unique' => 'Unit English name already exists (இந்த அளவீட்டு ஆங்கில பெயர் ஏற்கனவே உள்ளது).',
            'name_en.max' => 'Unit English name cannot exceed 255 characters (ஆங்கில பெயர் 255 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'name_ta.required' => 'Unit Tamil name is required (அளவீட்டின் தமிழ் பெயர் அவசியம்).',
            'name_ta.unique' => 'Unit Tamil name already exists (இந்த அளவீட்டு தமிழ் பெயர் ஏற்கனவே உள்ளது).',
            'name_ta.max' => 'Unit Tamil name cannot exceed 255 characters (தமிழ் பெயர் 255 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'code.required' => 'Unit symbol / code is required (குறியீடு / குறியீட்டு எண் அவசியம்).',
            'code.unique' => 'This unit symbol / code already exists (இந்த குறியீடு ஏற்கனவே உள்ளது).',
            'code.max' => 'Unit symbol / code cannot exceed 50 characters (குறியீடு 50 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'usage.max' => 'Usage example cannot exceed 255 characters (பயன்பாட்டு விளக்கம் 255 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'status.required' => 'Please select a status (நிலை தேர்ந்தெடுக்கவும்).',
            'status.in' => 'Selected status must be Active or Inactive (நிலை Active அல்லது Inactive ஆக இருக்க வேண்டும்).',
        ];
    }
}
