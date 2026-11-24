<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAdminLedgerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare data before validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('supplier_payment_scope')) {
            $this->merge([
                'is_site_based' => $this->input('supplier_payment_scope') === 'site_based',
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
        $category = $this->input('category', 'supplier_payment');
        $isSiteBased = $this->boolean('is_site_based', true);

        $rules = [
            'entry_date' => ['required', 'date'],
            'category' => ['required', 'string', 'in:supplier_payment,client_payment'],
            'supplier_payment_scope' => ['nullable', 'string', 'in:site_based,total'],
            'is_site_based' => ['nullable', 'boolean'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_mode' => ['required', 'string', 'max:50'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'cheque_no' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'receipt_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,pdf', 'max:5120'],
        ];

        if ($category === 'client_payment') {
            $rules['client_id'] = ['required', 'integer', 'exists:clients,id'];
            $rules['project_site_id'] = ['required', 'integer', 'exists:project_sites,id'];
        } else {
            // Supplier Payment
            $rules['supplier_id'] = ['required', 'integer', 'exists:suppliers,id'];
            if ($isSiteBased) {
                $rules['project_site_id'] = ['required', 'integer', 'exists:project_sites,id'];
                $rules['purchase_order_no'] = ['nullable', 'string', 'max:100'];
            } else {
                $rules['project_site_id'] = ['nullable', 'integer', 'exists:project_sites,id'];
                $rules['purchase_order_no'] = ['nullable', 'string', 'max:100'];
            }
        }

        return $rules;
    }

    /**
     * Custom attribute names for validation errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'entry_date' => 'Entry Date',
            'category' => 'Payment Category',
            'amount' => 'Amount',
            'client_id' => 'Client',
            'project_site_id' => 'Project Site',
            'supplier_id' => 'Supplier',
            'payment_mode' => 'Payment Mode',
        ];
    }
}
