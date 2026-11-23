<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRentalRequest extends FormRequest
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
        return [
            'property_name' => ['required', 'string', 'max:255'],
            'shop_home_office_name' => ['nullable', 'string', 'max:255'],
            'property_type' => ['required', 'string', 'in:Commercial Shop,Residential Flat,Office Space,Warehouse / Shed'],
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
            'property_name.required' => 'Property or shop name is required (சொத்தின் பெயர் அவசியம்).',
            'property_type.required' => 'Please select a property type (வகை அவசியம்).',
            'property_type.in' => 'Selected property type is invalid.',
            'location.required' => 'Property location or address is required (முகவரி அவசியம்).',
            'monthly_rent.required' => 'Monthly rent is required (மாத வாடகை அவசியம்).',
            'monthly_rent.numeric' => 'Monthly rent must be a valid number.',
            'monthly_rent.min' => 'Monthly rent cannot be negative.',
            'security_deposit.numeric' => 'Security deposit must be a valid number.',
            'security_deposit.min' => 'Security deposit cannot be negative.',
            'status.required' => 'Please select a status (நிலை அவசியம்).',
            'status.in' => 'Selected status must be Active or Inactive.',
        ];
    }
}
