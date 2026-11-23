<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBranchRequest extends FormRequest
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
        if ($this->has('branch_name')) {
            $this->merge([
                'branch_name' => trim((string) $this->branch_name),
            ]);
        }

        if ($this->has('branch_name_ta')) {
            $ta = trim((string) $this->branch_name_ta);
            $this->merge([
                'branch_name_ta' => $ta !== '' ? $ta : null,
            ]);
        }

        if ($this->has('ifsc_code')) {
            $this->merge([
                'ifsc_code' => strtoupper(trim((string) $this->input('ifsc_code'))),
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
            'bank_id' => ['required', 'integer', 'exists:banks,id'],
            'branch_name' => ['required', 'string', 'max:255', Rule::unique('branches', 'branch_name')],
            'branch_name_ta' => ['nullable', 'string', 'max:255', Rule::unique('branches', 'branch_name_ta')],
            'ifsc_code' => [
                'required',
                'string',
                'size:11',
                'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/i',
                Rule::unique('branches', 'ifsc_code'),
            ],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:25', 'regex:/^[0-9+\-\s()]{7,25}$/'],
            'valuation_fee' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
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
            'bank_id.required' => 'Please select a bank (வங்கி பெயர் தேர்வு செய்யவும்).',
            'bank_id.integer' => 'Selected bank ID must be valid (சரியான வங்கி அடையாள எண் தேர்வு செய்யவும்).',
            'bank_id.exists' => 'Selected bank is invalid or does not exist (தேர்ந்தெடுக்கப்பட்ட வங்கி தவறானது).',
            'branch_name.required' => 'Branch name is required (வங்கி கிளை பெயர் அவசியம்).',
            'branch_name.unique' => 'This branch name already exists (இந்த வங்கி கிளை பெயர் ஏற்கனவே உள்ளது).',
            'branch_name.max' => 'Branch name cannot exceed 255 characters (வங்கி கிளை பெயர் 255 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'branch_name_ta.unique' => 'This Tamil branch name already exists (இந்த தமிழ் வங்கி கிளை பெயர் ஏற்கனவே உள்ளது).',
            'branch_name_ta.max' => 'Branch name in Tamil cannot exceed 255 characters (தமிழ் பெயர் 255 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'ifsc_code.required' => 'IFSC code is required (IFSC குறியீடு அவசியம்).',
            'ifsc_code.size' => 'IFSC code must be exactly 11 characters (IFSC குறியீடு சரியாக 11 எழுத்துகளாக இருக்க வேண்டும்).',
            'ifsc_code.regex' => 'Please enter a valid IFSC code format (சரியான IFSC குறியீட்டு வடிவத்தை உள்ளிடவும், எ.கா. SBIN0001234).',
            'ifsc_code.unique' => 'This IFSC code is already registered for another branch (இந்த IFSC குறியீடு ஏற்கனவே பதிவு செய்யப்பட்டுள்ளது).',
            'contact_person.max' => 'Contact person name cannot exceed 255 characters (தொடர்பு நபர் பெயர் 255 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'phone.regex' => 'Please enter a valid phone number (சரியான தொலைபேசி எண் உள்ளிடவும்).',
            'phone.max' => 'Phone number cannot exceed 25 characters (தொலைபேசி எண் 25 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'valuation_fee.numeric' => 'Valuation fee must be a valid number (மதிப்பீட்டு கட்டணம் சரியான எண்ணாக இருக்க வேண்டும்).',
            'valuation_fee.min' => 'Valuation fee cannot be negative (மதிப்பீட்டு கட்டணம் எதிர்மறையாக இருக்கக்கூடாது).',
            'valuation_fee.max' => 'Valuation fee cannot exceed ₹10,00,000 (மதிப்பீட்டு கட்டணம் அதிகபட்சம் ₹10,00,000 மட்டுமே).',
            'status.required' => 'Please select a status (நிலை தேர்ந்தெடுக்கவும்).',
            'status.in' => 'Selected status must be Active or Inactive (நிலை Active அல்லது Inactive ஆக இருக்க வேண்டும்).',
        ];
    }
}
