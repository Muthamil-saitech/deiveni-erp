<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLedgerRequest extends FormRequest
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
        if ($this->has('entry_date')) {
            $this->merge(['entry_date' => trim((string) $this->entry_date)]);
        }

        if ($this->has('flow')) {
            $this->merge(['flow' => trim((string) $this->flow)]);
        }

        if ($this->has('source')) {
            $this->merge(['source' => trim((string) $this->source)]);
        }

        if ($this->has('amount')) {
            $this->merge([
                'amount' => ($this->amount !== null && $this->amount !== '') ? trim((string) $this->amount) : null,
            ]);
        }

        if ($this->has('description')) {
            $this->merge(['description' => trim((string) $this->description)]);
        } elseif ($this->has('desc')) {
            $this->merge(['description' => trim((string) $this->desc)]);
        }

        if ($this->has('project_site_id') && ($this->project_site_id === '' || $this->project_site_id === null)) {
            $this->merge(['project_site_id' => null]);
        }

        if ($this->has('bank_id') && ($this->bank_id === '' || $this->bank_id === null)) {
            $this->merge(['bank_id' => null]);
        }

        if ($this->has('branch_id') && ($this->branch_id === '' || $this->branch_id === null)) {
            $this->merge(['branch_id' => null]);
        }

        if ($this->has('property_id') && ($this->property_id === '' || $this->property_id === null)) {
            $this->merge(['property_id' => null]);
        }

        if ($this->has('property_type_id') && ($this->property_type_id === '' || $this->property_type_id === null)) {
            $this->merge(['property_type_id' => null]);
        }

        if ($this->has('supplier_id') && ($this->supplier_id === '' || $this->supplier_id === null)) {
            $this->merge(['supplier_id' => null]);
        }

        if ($this->has('bank_customer_ref')) {
            $this->merge(['bank_customer_ref' => trim((string) $this->bank_customer_ref) ?: null]);
        }

        if ($this->has('client_name')) {
            $this->merge(['client_name' => trim((string) $this->client_name) ?: null]);
        }

        if ($this->has('cash_received_from')) {
            $this->merge(['cash_received_from' => trim((string) $this->cash_received_from) ?: null]);
        }

        if ($this->has('site_name')) {
            $this->merge(['site_name' => trim((string) $this->site_name) ?: null]);
        }

        if ($this->has('office_expense_details')) {
            $this->merge(['office_expense_details' => trim((string) $this->office_expense_details) ?: null]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $projectSiteRules = [
            'nullable',
            Rule::requiredIf(fn () => $this->input('source') === 'Construction'),
        ];
        if (is_numeric($this->input('project_site_id'))) {
            $projectSiteRules[] = 'exists:project_sites,id';
        }

        $rules = [
            'entry_date' => ['required', 'date'],
            'flow' => ['required', 'string', Rule::in(['income', 'expense'])],
            'source' => [
                'required',
                'string',
                Rule::in(['Construction', 'Bank Valuation', 'Rental', 'Supplier', 'Office and other expenses']),
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string', 'max:1000'],
            'desc' => ['nullable', 'string', 'max:1000'],

            'project_site_id' => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('source') === 'Construction' && $this->has('project_site_id')),
            ],
            'site_name' => ['nullable', 'string', 'max:255'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'cash_received_from' => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('flow') === 'income'
                    && $this->input('source') === 'Construction'
                    && filled($this->input('project_site_id'))),
                Rule::in(['client', 'owner']),
            ],

            'bank_id' => [
                'nullable',
                Rule::requiredIf(fn () => in_array($this->input('source'), ['Bank Valuation', 'Bank'], true) && $this->has('bank_id')),
                'exists:banks,id',
            ],
            'branch_id' => [
                'nullable',
                Rule::requiredIf(fn () => in_array($this->input('source'), ['Bank Valuation', 'Bank'], true) && $this->has('branch_id')),
                'exists:branches,id',
            ],
            'bank_customer_ref' => ['nullable', 'string', 'max:255'],

            'property_id' => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('source') === 'Rental' && $this->has('property_id')),
                'exists:properties,id',
            ],
            'property_type_id' => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('source') === 'Rental' && $this->has('property_type_id')),
                'exists:property_types,id',
            ],

            'supplier_id' => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('source') === 'Supplier' && $this->has('supplier_id')),
                'exists:suppliers,id',
            ],
            'supplier_name' => ['nullable', 'string', 'max:255'],

            'office_expense_details' => ['nullable', 'string', 'max:255'],

            'transaction_type_id' => ['nullable', 'integer', 'exists:transaction_types,id'],
            'transaction_type_name' => ['nullable', 'string', 'max:255'],
            'transaction_type_value' => ['nullable', 'string', 'max:500'],
            'transaction_types' => ['nullable', 'array'],
            'transaction_types.*' => ['nullable', 'string', 'max:500'],

            'receipt_image' => ['nullable'],
            'image' => ['nullable', 'string'],
            'daily_allocation_id' => ['nullable', 'integer', 'exists:daily_allocations,id'],
            'wage_item_ids' => ['nullable', 'array'],
            'wage_item_ids.*' => ['integer'],
            'wage_cash_source' => ['nullable', 'string', 'in:office_cash,project_cash'],
            'extra_workers' => ['nullable', 'array'],
            'extra_workers.*.name' => ['nullable', 'string', 'max:255'],
            'extra_workers.*.work' => ['nullable', 'string', 'max:255'],
            'extra_workers.*.salary' => ['nullable', 'numeric', 'min:0'],
            'is_am_wage_payment' => ['nullable'],
        ];

        if ($this->hasFile('receipt_image')) {
            $rules['receipt_image'] = ['file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'];
        }

        return $rules;
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'entry_date.required' => 'Entry date is required (பதிவு தேதி அவசியம்).',
            'entry_date.date' => 'Please enter a valid entry date (சரியான தேதியை உள்ளிடவும்).',

            'flow.required' => 'Please select entry flow type (வரவு/செலவு வகையை தேர்வு செய்யவும்).',
            'flow.in' => 'Flow must be either Income or Expense (வரவு அல்லது செலவு மட்டுமே தேர்ந்தெடுக்க முடியும்).',

            'source.required' => 'Please select a source / category (பிரிவு அல்லது மூலத்தை தேர்வு செய்யவும்).',
            'source.in' => 'Selected source / category is invalid (தேர்ந்தெடுக்கப்பட்ட பிரிவு தவறானது).',

            'amount.required' => 'Amount is required (தொகை அவசியம்).',
            'amount.numeric' => 'Amount must be a valid number (தொகை சரியான எண் வடிவில் இருக்க வேண்டும்).',
            'amount.min' => 'Amount must be greater than zero (தொகை 0 ஐ விட அதிகமாக இருக்க வேண்டும்).',

            'description.required' => 'Description / narration is required (விவரம் / குறிப்பு அவசியம்).',
            'description.string' => 'Description must be a valid text (விவரம் சரியான எழுத்தாக இருக்க வேண்டும்).',
            'description.max' => 'Description cannot exceed 1000 characters (விவரம் 1000 எழுத்துகளுக்கு மிகாமல் இருக்க வேண்டும்).',

            'project_site_id.required' => 'Please select an associated site (கட்டுமான தளத்தை தேர்வு செய்யவும்).',
            'project_site_id.exists' => 'The selected site does not exist (தேர்ந்தெடுக்கப்பட்ட தளம் தவறானது அல்லது இல்லை).',

            'client_name.max' => 'Client name cannot exceed 255 characters (வாடிக்கையாளர் பெயர் 255 எழுத்துகளுக்கு மிகாமல் இருக்க வேண்டும்).',
            'site_name.max' => 'Site name cannot exceed 255 characters (தளத்தின் பெயர் 255 எழுத்துகளுக்கு மிகாமல் இருக்க வேண்டும்).',

            'bank_id.required' => 'Please select a bank (வங்கியை தேர்வு செய்யவும்).',
            'bank_id.exists' => 'The selected bank does not exist (தேர்ந்தெடுக்கப்பட்ட வங்கி தவறானது அல்லது இல்லை).',

            'branch_id.required' => 'Please select a branch (வங்கி கிளையை தேர்வு செய்யவும்).',
            'branch_id.exists' => 'The selected branch does not exist (தேர்ந்தெடுக்கப்பட்ட கிளை தவறானது அல்லது இல்லை).',

            'bank_customer_ref.max' => 'Customer / loan reference cannot exceed 255 characters (வாடிக்கையாளர் குறிப்பு 255 எழுத்துகளுக்கு மிகாமல் இருக்க வேண்டும்).',

            'property_id.required' => 'Please select a property (வாடகை சொத்தை தேர்வு செய்யவும்).',
            'property_id.exists' => 'The selected property does not exist (தேர்ந்தெடுக்கப்பட்ட சொத்து தவறானது அல்லது இல்லை).',

            'property_type_id.required' => 'Please select a property type / unit (சொத்து வகை / அலகை தேர்வு செய்யவும்).',
            'property_type_id.exists' => 'The selected property type / unit does not exist (தேர்ந்தெடுக்கப்பட்ட அலகு தவறானது அல்லது இல்லை).',

            'supplier_id.required' => 'Please select a supplier (சப்ளையரை தேர்வு செய்யவும்).',
            'supplier_id.exists' => 'The selected supplier does not exist (தேர்ந்தெடுக்கப்பட்ட சப்ளையர் தவறானது அல்லது இல்லை).',
            'supplier_name.max' => 'Supplier name cannot exceed 255 characters (சப்ளையர் பெயர் 255 எழுத்துகளுக்கு மிகாமல் இருக்க வேண்டும்).',

            'office_expense_details.max' => 'Office expense details cannot exceed 255 characters (அலுவலக செலவு விவரம் 255 எழுத்துகளுக்கு மிகாமல் இருக்க வேண்டும்).',

            'receipt_image.file' => 'Receipt must be a valid file (ரசீது சரியான கோப்பாக இருக்க வேண்டும்).',
            'receipt_image.image' => 'The receipt must be an image file (ரசீது படக் கோப்பாக இருக்க வேண்டும்).',
            'receipt_image.mimes' => 'The receipt must be a PNG, JPG, JPEG, or WEBP image (ரசீது PNG, JPG, JPEG அல்லது WEBP படமாக இருக்க வேண்டும்).',
            'receipt_image.max' => 'The receipt image size cannot exceed 5MB (ரசீது படம் 5MB க்கு மிகாமல் இருக்க வேண்டும்).',
        ];
    }

    /**
     * Custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'entry_date' => 'entry date (பதிவு தேதி)',
            'flow' => 'entry flow type (பரிவர்த்தனை வகை)',
            'source' => 'source / category (பிரிவு)',
            'amount' => 'amount (தொகை)',
            'description' => 'description (விவரம்)',
            'project_site_id' => 'project site (கட்டுமான தளம்)',
            'client_name' => 'client name (வாடிக்கையாளர் பெயர்)',
            'supplier_id' => 'supplier (சப்ளையர்)',
            'bank_id' => 'bank (வங்கி)',
            'branch_id' => 'branch (வங்கி கிளை)',
            'bank_customer_ref' => 'customer / loan reference (வாடிக்கையாளர் குறிப்பு)',
            'property_id' => 'property (சொத்து)',
            'property_type_id' => 'property type / unit (சொத்து அலகு)',
            'office_expense_details' => 'office expense details (அலுவலக செலவு விவரம்)',
            'receipt_image' => 'receipt image (ரசீது படம்)',
        ];
    }
}
