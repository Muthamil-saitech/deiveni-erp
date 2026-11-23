<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseMovementRequest extends FormRequest
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
        if (! $this->has('movement_type') || blank($this->movement_type)) {
            $this->merge(['movement_type' => 'Stock Inward']);
        }

        if ($this->has('notes') && filled($this->notes)) {
            $this->merge(['notes' => trim((string) $this->notes)]);
        }

        if ($this->has('reference_no') && filled($this->reference_no)) {
            $this->merge(['reference_no' => trim((string) $this->reference_no)]);
        }

        if ($this->has('site_name') && filled($this->site_name)) {
            $this->merge(['site_name' => trim((string) $this->site_name)]);
        }

        if ($this->has('cash_amount') && (trim((string) $this->cash_amount) === '' || $this->cash_amount === null)) {
            $this->merge(['cash_amount' => null]);
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
            'warehouse_item_id' => ['required', 'exists:warehouse_items,id'],
            'movement_type' => ['required', 'string', 'in:Issue to Site,Stock Inward,Adjustment'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'cash_amount' => ['nullable', 'numeric', 'min:0'],
            'project_site_id' => ['nullable', 'exists:project_sites,id'],
            'site_name' => ['nullable', 'string', 'max:255'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'movement_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
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
            'warehouse_item_id.required' => 'Please select a valid item to restock (சரக்கு பொருளை தேர்வு செய்க).',
            'warehouse_item_id.exists' => 'Selected warehouse item does not exist (தேர்ந்தெடுக்கப்பட்ட பொருள் பட்டியலில் இல்லை).',

            'movement_type.required' => 'Movement type is required (இயக்க வகை அவசியம்).',
            'movement_type.in' => 'Invalid movement type (செல்லுபடியாகாத இயக்க வகை).',

            'quantity.required' => 'Please enter a valid quantity greater than zero (0-ஐ விட அதிகமான அளவை உள்ளிடவும்).',
            'quantity.numeric' => 'Quantity must be a valid number (அளவு எண் வடிவில் இருக்க வேண்டும்).',
            'quantity.min' => 'Please enter a valid quantity greater than zero (அளவு 0-ஐ விட அதிகமாக இருக்க வேண்டும்).',

            'cash_amount.numeric' => 'Cash amount / unit price must be a valid number (விலை எண் வடிவில் இருக்க வேண்டும்).',
            'cash_amount.min' => 'Cash amount / unit price must be 0 or more (விலை 0 அல்லது அதற்கு மேல் இருக்க வேண்டும்).',

            'project_site_id.exists' => 'Selected project site does not exist (தேர்ந்தெடுக்கப்பட்ட தளம் செல்லாது).',
            'site_name.max' => 'Site name cannot exceed 255 characters (தளத்தின் பெயர் 255 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'reference_no.max' => 'Reference number cannot exceed 100 characters (குறிப்பு எண் 100 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'movement_date.date' => 'Movement date must be a valid date (தேதி சரியான வடிவில் இருக்க வேண்டும்).',
            'notes.max' => 'Remarks cannot exceed 500 characters (குறிப்பு 500 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
        ];
    }
}
