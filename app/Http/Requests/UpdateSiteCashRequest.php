<?php

namespace App\Http\Requests;

use App\Models\ProjectSite;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSiteCashRequest extends FormRequest
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
        // If site name passed instead of project_site_id
        if (! $this->filled('project_site_id') && $this->filled('site')) {
            $site = ProjectSite::query()->where('name', $this->input('site'))->first();
            if ($site) {
                $this->merge(['project_site_id' => $site->id]);
            }
        }

        if (! $this->filled('transaction_type') && $this->filled('type')) {
            $this->merge(['transaction_type' => $this->input('type')]);
        }

        if (! $this->filled('bill_no') && $this->filled('bill')) {
            $this->merge(['bill_no' => $this->input('bill')]);
        }

        if (! $this->filled('description') && $this->filled('desc')) {
            $this->merge(['description' => $this->input('desc')]);
        }

        if (! $this->filled('supplier_name') && $this->filled('supplier')) {
            $this->merge(['supplier_name' => $this->input('supplier')]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'project_site_id' => ['required', 'exists:project_sites,id'],
            'transaction_type' => ['nullable', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_mode' => ['nullable', 'string', 'in:Cash,Cheque,UPI'],
            'payment_reference' => [
                'nullable',
                'string',
                'max:100',
                Rule::requiredIf(fn () => in_array($this->input('payment_mode'), ['Cheque', 'UPI'], true)),
            ],
            'paid_to' => ['nullable', 'string', 'max:50'],
            'receiver_name' => ['nullable', 'string', 'max:100'],
            'paid_by' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'upi_id' => ['nullable', 'string', 'max:100'],
            'account_holder_name' => ['nullable', 'string', 'max:100'],
            'wage_cash_source' => ['nullable', 'string', 'in:office_cash,project_cash,cash_transfer'],
            'ledger_id' => ['nullable', 'integer'],
            'cash_transfer_id' => ['nullable', 'integer', 'exists:cash_transfers,id'],
            'returned_wage_ids' => ['nullable'],
            'supplier_id' => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('transaction_type') === 'Supplier Payment'
                    && $this->input('material_source') !== 'own_warehouse'
                    && ! in_array($this->input('supplier_name'), ['Central Warehouse', 'Own Warehouse'], true)),
                'exists:suppliers,id',
            ],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'purchase_order_no' => ['nullable', 'string', 'max:255'],
            'bill_no' => ['nullable', 'string', 'max:255'],
            'payment_date' => ['nullable', 'date'],
            'payment_time' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'receipt_image' => ['nullable'],
            'items' => ['nullable'],
        ];

        if ($this->hasFile('receipt_image')) {
            $rules['receipt_image'] = ['nullable', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'];
        }

        return $rules;
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'project_site_id.required' => 'Please select a project site (திட்ட தளத்தை தேர்வு செய்யவும்).',
            'project_site_id.exists' => 'The selected project site does not exist (தேர்ந்தெடுக்கப்பட்ட தளம் தவறானது அல்லது இல்லை).',
            'amount.required' => 'Please enter a valid amount (தொகையை உள்ளிடவும்).',
            'amount.numeric' => 'The amount must be a number (தொகை எண்ணாக இருக்க வேண்டும்).',
            'amount.min' => 'The amount must be at least ₹0.01 (தொகை குறைந்தது ₹0.01 ஆக இருக்க வேண்டும்).',
            'payment_mode.in' => 'Please select a valid payment mode (செல்லுபடியாகும் கட்டண முறையை தேர்வு செய்யவும்).',
            'payment_reference.required' => 'Payment reference is mandatory for Cheque or UPI payments (காசோலை அல்லது UPI பரிவர்த்தனைக்கு குறிப்பு எண் அவசியம்).',
            'supplier_id.required' => 'Please select a supplier for supplier payment (சப்ளையரை தேர்வு செய்யவும்).',
            'supplier_id.exists' => 'The selected supplier does not exist (தேர்ந்தெடுக்கப்பட்ட சப்ளையர் தவறானது அல்லது இல்லை).',
            'receipt_image.image' => 'The receipt must be an image file (ரசீது பட கோப்பாக இருக்க வேண்டும்).',
            'receipt_image.mimes' => 'The receipt must be a file of type: png, jpg, jpeg, webp.',
            'receipt_image.max' => 'The receipt image must not be greater than 5MB.',
        ];
    }
}
