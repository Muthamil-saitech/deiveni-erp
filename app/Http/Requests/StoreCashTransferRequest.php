<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCashTransferRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'transfer_direction' => ['required', 'string', 'in:office_to_site,site_to_office,project_cash'],
            'project_site_id' => ['required_if:transfer_direction,project_cash', 'nullable', 'exists:project_sites,id'],
            'transfer_date' => ['required', 'date'],
            'transfer_time' => ['nullable', 'string', 'max:20'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_mode' => ['required', 'string', 'in:Cash,UPI,Bank Transfer,Cheque'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'handed_over_by' => ['required', 'string', 'max:100'],
            'received_by' => ['required', 'string', 'max:100'],
            'purpose' => ['nullable', 'string', 'max:1000'],
            'is_temporary' => ['nullable', 'boolean'],
            'expected_return_date' => ['nullable', 'date'],
            'acceptance_status' => ['nullable', 'string', 'in:pending,accepted'],
            'acceptance_notes' => ['nullable', 'string', 'max:1000'],
            'receipt_image' => ['nullable', 'image', 'max:5120'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'transfer_direction.required' => 'Please select the transfer direction (Office to Site or Site to Office).',
            'amount.required' => 'Transfer amount is required.',
            'amount.min' => 'Amount must be greater than zero.',
            'handed_over_by.required' => 'Please enter who handed over the cash.',
            'received_by.required' => 'Please enter who received the cash.',
            'payment_mode.required' => 'Payment mode is required.',
        ];
    }
}
