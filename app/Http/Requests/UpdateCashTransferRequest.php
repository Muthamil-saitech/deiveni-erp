<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCashTransferRequest extends FormRequest
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
            'transfer_direction' => ['sometimes', 'required', 'string', 'in:office_to_site,site_to_office,project_cash'],
            'project_site_id' => ['nullable', 'exists:project_sites,id'],
            'transfer_date' => ['sometimes', 'required', 'date'],
            'transfer_time' => ['nullable', 'string', 'max:20'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'payment_mode' => ['sometimes', 'required', 'string', 'in:Cash,UPI,Bank Transfer,Cheque'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'handed_over_by' => ['sometimes', 'required', 'string', 'max:100'],
            'received_by' => ['sometimes', 'required', 'string', 'max:100'],
            'purpose' => ['nullable', 'string', 'max:1000'],
            'is_temporary' => ['nullable', 'boolean'],
            'expected_return_date' => ['nullable', 'date'],
            'settlement_status' => ['nullable', 'string', 'in:pending,settled'],
            'settlement_notes' => ['nullable', 'string', 'max:1000'],
            'acceptance_status' => ['nullable', 'string', 'in:pending,accepted'],
            'acceptance_notes' => ['nullable', 'string', 'max:1000'],
            'receipt_image' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
