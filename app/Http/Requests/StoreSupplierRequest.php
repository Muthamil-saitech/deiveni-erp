<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierRequest extends FormRequest
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
            $this->merge(['name' => trim((string) $this->name)]);
        }

        if ($this->has('contact_person')) {
            $this->merge(['contact_person' => trim((string) $this->contact_person)]);
        }

        if ($this->has('phone')) {
            $this->merge(['phone' => trim((string) $this->phone)]);
        }

        if ($this->has('address')) {
            $this->merge(['address' => trim((string) $this->address)]);
        }

        if ($this->has('gst_number')) {
            $gst = strtoupper(trim((string) $this->gst_number));
            $this->merge(['gst_number' => $gst !== '' ? $gst : null]);
        }

        if ($this->has('materials') && is_string($this->materials)) {
            $decoded = json_decode($this->materials, true);
            if (is_array($decoded)) {
                $this->merge(['materials' => $decoded]);
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
            'name' => ['required', 'string', 'max:255', Rule::unique('suppliers', 'name')],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:25', 'regex:/^[0-9+\-\s()]{7,25}$/'],
            'address' => ['nullable', 'string', 'max:500'],
            'gst_number' => [
                'nullable',
                'string',
                'size:15',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i',
                Rule::unique('suppliers', 'gst_number'),
            ],
            'status' => ['nullable', 'string', 'in:Active,Inactive'],
            'materials' => ['required', 'array', 'min:1'],
            'materials.*.material_id' => ['required', 'integer', 'exists:materials,id', 'distinct'],
            'materials.*.price' => ['required', 'numeric', 'min:0'],
            'materials.*.grade' => ['nullable', 'string', 'max:50'],
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
            'name.required' => 'Supplier / company name is required (சப்ளையர் பெயர் அவசியம்).',
            'name.unique' => 'This supplier / company name already exists (இந்த சப்ளையர் பெயர் ஏற்கனவே உள்ளது).',
            'name.max' => 'Supplier name cannot exceed 255 characters (சப்ளையர் பெயர் 255 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'contact_person.max' => 'Contact person name cannot exceed 255 characters (தொடர்பு நபர் பெயர் 255 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'phone.required' => 'Phone number is required (தொலைபேசி எண் அவசியம்).',
            'phone.regex' => 'Please enter a valid phone number (சரியான தொலைபேசி எண் பதிவிடவும்).',
            'phone.max' => 'Phone number cannot exceed 25 characters (தொலைபேசி எண் 25 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'address.max' => 'Address cannot exceed 500 characters (முகவரி 500 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'gst_number.regex' => 'Please enter a valid 15-digit GSTIN (சரியான 15 இலக்க GST எண்ணை உள்ளிடவும்).',
            'gst_number.size' => 'GSTIN must be exactly 15 characters (GST எண் சரியாக 15 எழுத்துகளாக இருக்க வேண்டும்).',
            'gst_number.unique' => 'This GST number is already registered (இந்த GST எண் ஏற்கனவே பதிவு செய்யப்பட்டுள்ளது).',
            'gst_number.max' => 'GST number cannot exceed 50 characters (GST எண் 50 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'status.in' => 'Selected status must be Active or Inactive (நிலை Active அல்லது Inactive ஆக இருக்க வேண்டும்).',
            'materials.required' => 'Please select at least one material (குறைந்தது ஒரு பொருளாவது தேர்ந்தெடுக்கவும்).',
            'materials.array' => 'Invalid materials format (பொருட்கள் தவறான வடிவத்தில் உள்ளன).',
            'materials.min' => 'Please select at least one material (குறைந்தது ஒரு பொருளாவது தேர்ந்தெடுக்கவும்).',
            'materials.*.material_id.required' => 'Please select a material (பொருளைத் தேர்ந்தெடுக்கவும்).',
            'materials.*.material_id.integer' => 'Please select a valid material (சரியான பொருளைத் தேர்ந்தெடுக்கவும்).',
            'materials.*.material_id.exists' => 'The selected material does not exist (தேர்ந்தெடுக்கப்பட்ட பொருள் தவறானது).',
            'materials.*.material_id.distinct' => 'Duplicate material selected. Please select distinct materials (ஒரே பொருளை மீண்டும் தேர்ந்தெடுக்க வேண்டாம்).',
            'materials.*.price.required' => 'Rate per unit is required (விலை உள்ளிடவும்).',
            'materials.*.price.numeric' => 'Rate per unit must be a valid number (விலை சரியான எண்ணாக இருக்க வேண்டும்).',
            'materials.*.price.min' => 'Rate per unit cannot be negative (விலை எதிர்மறையாக இருக்கக்கூடாது).',
            'materials.*.grade.max' => 'Material grade cannot exceed 50 characters (தரம் 50 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
        ];
    }
}
