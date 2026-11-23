<?php

namespace App\Http\Requests;

use App\Models\Unit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaterialRequest extends FormRequest
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
        if ($this->has('name_en')) {
            $this->merge(['name_en' => trim((string) $this->name_en)]);
        }

        if ($this->has('name_ta')) {
            $this->merge(['name_ta' => trim((string) $this->name_ta)]);
        }

        if (! $this->filled('unit_id') && $this->filled('unit')) {
            $val = $this->input('unit');
            if (is_numeric($val)) {
                $this->merge(['unit_id' => (int) $val]);
            } else {
                $unit = Unit::where('name_en', $val)
                    ->orWhere('code', $val)
                    ->orWhere('name_ta', $val)
                    ->first();
                if ($unit) {
                    $this->merge(['unit_id' => $unit->id]);
                }
            }
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
            'name_en' => ['required', 'string', 'max:255', Rule::unique('materials', 'name_en')],
            'name_ta' => ['required', 'string', 'max:255', Rule::unique('materials', 'name_ta')],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
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
            'name_en.required' => 'Material English name is required (பொருளின் ஆங்கில பெயர் அவசியம்).',
            'name_en.unique' => 'Material English name already exists (இந்தப் பொருள் ஆங்கில பெயர் ஏற்கனவே உள்ளது).',
            'name_en.max' => 'Material English name cannot exceed 255 characters (ஆங்கில பெயர் 255 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'name_ta.required' => 'Material Tamil name is required (பொருளின் தமிழ் பெயர் அவசியம்).',
            'name_ta.unique' => 'Material Tamil name already exists (இந்தப் பொருள் தமிழ் பெயர் ஏற்கனவே உள்ளது).',
            'name_ta.max' => 'Material Tamil name cannot exceed 255 characters (தமிழ் பெயர் 255 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'unit_id.required' => 'Please select a unit of measurement (அளவீட்டு அலகு அவசியம்).',
            'unit_id.exists' => 'Selected measurement unit does not exist (தேர்ந்தெடுக்கப்பட்ட அளவீட்டு அலகு தவறானது).',
            'status.required' => 'Please select a status (நிலை தேர்ந்தெடுக்கவும்).',
            'status.in' => 'Selected status must be Active or Inactive (நிலை Active அல்லது Inactive ஆக இருக்க வேண்டும்).',
        ];
    }
}
