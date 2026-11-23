<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionTypeRequest extends FormRequest
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

        if ($this->has('category')) {
            $this->merge(['category' => trim((string) $this->category)]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $txnTypeId = $this->route('id') ?? $this->route('transaction_type') ?? $this->input('id');

        return [
            'name_en' => [
                'required',
                'string',
                'max:100',
                Rule::unique('transaction_types', 'name_en')->ignore($txnTypeId),
            ],
            'category' => ['sometimes', 'required', 'string', 'in:Construction,Bank Valuation,Rental,Supplier,Office and other expenses'],
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
            'name_en.required' => 'Transaction Type Name is required (பரிவர்த்தனை பெயர் தேவை).',
            'name_en.unique' => 'This transaction type name is already registered (இந்த பரிவர்த்தனை வகை ஏற்கனவே பதிவு செய்யப்பட்டுள்ளது).',
            'category.required' => 'Please select a Transaction Category (பரிவர்த்தனை வகையை தேர்வு செய்க).',
            'category.in' => 'Selected Transaction Category is invalid (செல்லுபடியாகாத வகை).',
            'status.required' => 'Status is required (நிலை தேவை).',
        ];
    }
}
