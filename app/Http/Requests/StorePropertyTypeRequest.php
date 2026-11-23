<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePropertyTypeRequest extends FormRequest
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
        if ($this->has('shop_home_office_name')) {
            $val = trim((string) $this->shop_home_office_name);
            $this->merge([
                'shop_home_office_name' => $val !== '' ? $val : null,
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
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'shop_home_office_name' => ['nullable', 'string', 'max:255', Rule::unique('property_types', 'shop_home_office_name')],
            'property_type' => ['required', 'string', 'in:Commercial Shop,Residential Flat,Office Space,Warehouse / Shed'],
            'floor_type' => ['nullable', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'tenant_name' => ['nullable', 'string', 'max:255'],
            'tenant_phone' => ['nullable', 'string', 'max:50'],
            'monthly_rent' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'security_deposit' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'rent_due_day' => ['nullable', 'string', 'max:50'],
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
            'property_id.required' => 'Please select a property (சொத்தின் பெயர் தேர்வு செய்யவும்).',
            'property_id.integer' => 'Selected property ID must be valid (சரியான சொத்து அடையாள எண் தேர்வு செய்யவும்).',
            'property_id.exists' => 'Selected property is invalid or does not exist (தேர்ந்தெடுக்கப்பட்ட சொத்து தவறானது).',
            'shop_home_office_name.unique' => 'This shop / home / office name already exists (இந்த கடை / வீடு / அலுவலக பெயர் ஏற்கனவே உள்ளது).',
            'shop_home_office_name.max' => 'Shop / home / office name cannot exceed 255 characters (கடை / வீடு / அலுவலக பெயர் 255 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'property_type.required' => 'Please select a property type (சொத்து வகை தேர்வு செய்யவும்).',
            'property_type.in' => 'Selected property type is invalid (தேர்ந்தெடுக்கப்பட்ட சொத்து வகை தவறானது).',
            'floor_type.max' => 'Floor type cannot exceed 255 characters (தள வகை 255 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'location.required' => 'Location or address is required (முகவரி அவசியம்).',
            'location.max' => 'Location cannot exceed 255 characters (முகவரி 255 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'tenant_name.max' => 'Tenant name cannot exceed 255 characters (வாடகைதாரர் பெயர் 255 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'tenant_phone.max' => 'Tenant phone number cannot exceed 50 characters (வாடகைதாரர் தொலைபேசி எண் 50 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'monthly_rent.required' => 'Monthly rent is required (மாத வாடகை அவசியம்).',
            'monthly_rent.numeric' => 'Monthly rent must be a valid number (மாத வாடகை சரியான எண்ணாக இருக்க வேண்டும்).',
            'monthly_rent.min' => 'Monthly rent cannot be negative (மாத வாடகை எதிர்மறையாக இருக்கக்கூடாது).',
            'monthly_rent.max' => 'Monthly rent cannot exceed ₹9,99,99,999 (மாத வாடகை வரம்பை தாண்டக்கூடாது).',
            'security_deposit.numeric' => 'Security deposit must be a valid number (முன்பணம் சரியான எண்ணாக இருக்க வேண்டும்).',
            'security_deposit.min' => 'Security deposit cannot be negative (முன்பணம் எதிர்மறையாக இருக்கக்கூடாது).',
            'security_deposit.max' => 'Security deposit cannot exceed ₹9,99,99,999 (முன்பணம் வரம்பை தாண்டக்கூடாது).',
            'rent_due_day.max' => 'Rent due day cannot exceed 50 characters (வாடகை செலுத்தும் நாள் 50 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'status.required' => 'Please select a status (நிலை தேர்ந்தெடுக்கவும்).',
            'status.in' => 'Selected status must be Active or Inactive (நிலை Active அல்லது Inactive ஆக இருக்க வேண்டும்).',
        ];
    }
}
