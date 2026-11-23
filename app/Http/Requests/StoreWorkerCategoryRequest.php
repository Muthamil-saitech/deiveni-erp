<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkerCategoryRequest extends FormRequest
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
        if ($this->has('name_ta')) {
            $this->merge(['name_ta' => trim((string) $this->name_ta)]);
        }

        if ($this->has('name_en')) {
            $this->merge(['name_en' => trim((string) $this->name_en)]);
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
            'name_ta' => ['required', 'string', 'max:255', Rule::unique('worker_categories', 'name_ta')],
            'name_en' => ['required', 'string', 'max:255', Rule::unique('worker_categories', 'name_en')],
            'daily_wage' => ['required', 'numeric', 'min:1', 'max:100000'],
            'shift' => ['nullable', 'string', 'max:100'],
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
            'name_ta.required' => 'Tamil category name is required (பிரிவின் தமிழ் பெயர் அவசியம்).',
            'name_ta.unique' => 'Tamil category name already exists (இந்தப் பிரிவு தமிழ் பெயர் ஏற்கனவே உள்ளது).',
            'name_ta.max' => 'Tamil category name cannot exceed 255 characters (தமிழ் பெயர் 255 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'name_en.required' => 'English category name is required (பிரிவின் ஆங்கில பெயர் அவசியம்).',
            'name_en.unique' => 'English category name already exists (இந்தப் பிரிவு ஆங்கில பெயர் ஏற்கனவே உள்ளது).',
            'name_en.max' => 'English category name cannot exceed 255 characters (ஆங்கில பெயர் 255 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'daily_wage.required' => 'Default daily wage is required (தினக்கூலி அவசியம்).',
            'daily_wage.numeric' => 'Daily wage must be a valid number (தினக்கூலி சரியான எண்ணாக இருக்க வேண்டும்).',
            'daily_wage.min' => 'Daily wage must be at least ₹1 (தினக்கூலி குறைந்தபட்சம் ₹1 இருக்க வேண்டும்).',
            'daily_wage.max' => 'Daily wage cannot exceed ₹1,00,000 (தினக்கூலி ₹1,00,000க்கு மிகாமல் இருக்க வேண்டும்).',
            'shift.max' => 'Shift details cannot exceed 100 characters (வேலை நேரம் 100 எழுத்துகளுக்கு மிகாமல் இருக்க வேண்டும்).',
            'status.required' => 'Please select a status (நிலை தேர்ந்தெடுக்கவும்).',
            'status.in' => 'Selected status must be Active or Inactive (நிலை Active அல்லது Inactive ஆக இருக்க வேண்டும்).',
        ];
    }
}
