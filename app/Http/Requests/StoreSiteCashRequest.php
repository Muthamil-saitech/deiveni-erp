<?php

namespace App\Http\Requests;

use App\Models\ProjectSite;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSiteCashRequest extends FormRequest
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
            'transaction_type' => ['required', 'string', 'max:100'],
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
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'project_site_id.required' => 'Please select a project site (கட்டுமான தளத்தை தேர்வு செய்யவும்).',
            'project_site_id.exists' => 'The selected project site does not exist (தேர்ந்தெடுக்கப்பட்ட தளம் தவறானது அல்லது இல்லை).',
            'transaction_type.required' => 'Please select a transaction flow type (பரிவர்த்தனை வகையை தேர்வு செய்யவும்).',
            'transaction_type.string' => 'Transaction type must be a valid text (பரிவர்த்தனை வகை சரியானதாக இருக்க வேண்டும்).',
            'transaction_type.max' => 'Transaction type cannot exceed 100 characters (பரிவர்த்தனை வகை 100 எழுத்துகளுக்கு மிகாமல் இருக்க வேண்டும்).',
            'amount.required' => 'Please enter the transaction amount (பரிவர்த்தனை தொகை உள்ளிடவும்).',
            'amount.numeric' => 'Amount must be a valid number (தொகை சரியான எண்ணாக இருக்க வேண்டும்).',
            'amount.min' => 'Amount must be greater than zero (தொகை பூஜ்ஜியத்தை விட அதிகமாக இருக்க வேண்டும்).',
            'payment_mode.in' => 'Please select a valid payment mode (Cash, Cheque, or UPI).',
            'payment_reference.required' => 'Please enter the reference number for the selected payment mode (காசோலை எண் அல்லது யுபிஐ குறிப்பு எண்ணை உள்ளிடவும்).',
            'payment_reference.max' => 'Payment reference cannot exceed 100 characters (குறிப்பு எண் 100 எழுத்துகளுக்கு மிகாமல் இருக்க வேண்டும்).',
            'paid_to.max' => 'Recipient cannot exceed 50 characters.',
            'supplier_id.required' => 'Please select a supplier for supplier payment (சப்ளையரை தேர்வு செய்யவும்).',
            'supplier_id.exists' => 'The selected supplier does not exist (தேர்ந்தெடுக்கப்பட்ட சப்ளையர் தவறானது அல்லது இல்லை).',
            'purchase_order_no.max' => 'Purchase order number cannot exceed 255 characters (கொள்முதல் ஆணை எண் மிக நீளமாக உள்ளது).',
            'bill_no.max' => 'Bill / voucher number cannot exceed 255 characters (பில் / ரசீது எண் மிக நீளமாக உள்ளது).',
            'payment_date.date' => 'Payment date must be a valid date (சரியான தேதியை உள்ளிடவும்).',
            'payment_time.max' => 'Payment time cannot exceed 50 characters (நேரம் மிக நீளமாக உள்ளது).',
            'description.max' => 'Description cannot exceed 1000 characters (விவரம் 1000 எழுத்துகளுக்கு மிகாமல் இருக்க வேண்டும்).',
            'receipt_image.image' => 'The receipt must be an image file (ரசீது படக் கோப்பாக இருக்க வேண்டும்).',
            'receipt_image.mimes' => 'The receipt must be a PNG, JPG, JPEG, or WEBP image file (ரசீது PNG, JPG, JPEG அல்லது WEBP படமாக இருக்க வேண்டும்).',
            'receipt_image.max' => 'The receipt image size must not exceed 5MB (ரசீது படம் 5MB க்கு மிகாமல் இருக்க வேண்டும்).',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'project_site_id' => 'project site (திட்ட தளம்)',
            'transaction_type' => 'transaction flow type (பரிவர்த்தனை வகை)',
            'amount' => 'amount (தொகை)',
            'supplier_id' => 'supplier (சப்ளையர்)',
            'purchase_order_no' => 'purchase order number (கொள்முதல் ஆணை எண்)',
            'bill_no' => 'bill / voucher number (பில் / ரசீது எண்)',
            'payment_date' => 'payment date (பரிவர்த்தனை தேதி)',
            'payment_time' => 'payment time (பரிவர்த்தனை நேரம்)',
            'receipt_image' => 'receipt image (ரசீது படம்)',
            'description' => 'description (விவரம்)',
        ];
    }
}
