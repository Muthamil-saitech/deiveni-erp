<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectSiteRequest extends FormRequest
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
        if ($this->has('existing_client_id') && ! $this->filled('client_id')) {
            $this->merge([
                'client_id' => $this->input('existing_client_id'),
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('project_sites', 'name'),
            ],
            'location' => ['required', 'string', 'max:255'],
            'work_type' => ['required', 'string', 'in:New Build,Alteration'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'estimated_budget' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'in:In Progress,Planning,Completed,On Hold'],
            'client_option' => ['nullable', 'string', 'in:new,existing'],
            'client_id' => [
                'nullable',
                'required_if:client_option,existing',
                'integer',
                'exists:clients,id',
            ],
            'client_name' => [
                'exclude_if:client_option,existing',
                'required_if:client_option,new',
                'required_without:client_id',
                'nullable',
                'string',
                'max:255',
            ],
            'client_phone' => [
                'exclude_if:client_option,existing',
                'required_if:client_option,new',
                'required_without:client_id',
                'nullable',
                'string',
                'max:50',
                Rule::unique('clients', 'phone'),
            ],
            'client_address' => ['nullable', 'string', 'max:500'],
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
            'name.required' => 'Site project name is required (தள பெயர் அவசியம்).',
            'name.unique' => 'Project site name has already been taken (தள பெயர் ஏற்கனவே உள்ளது).',
            'location.required' => 'Location / Area is required (இடம் / பகுதி அவசியம்).',
            'work_type.required' => 'Work type is required (பணி வகை அவசியம்).',
            'work_type.in' => 'Please select a valid work type.',
            'estimated_budget.required' => 'Estimated budget is required (மதிப்பீட்டு தொகை அவசியம்).',
            'estimated_budget.numeric' => 'Estimated budget must be a number.',
            'estimated_budget.min' => 'Estimated budget cannot be negative.',
            'end_date.after_or_equal' => 'End date must be on or after start date.',
            'status.required' => 'Project status is required (நிலை அவசியம்).',
            'client_id.required_if' => 'Please select an existing customer (வாடிக்கையாளரை தேர்ந்தெடுக்கவும்).',
            'client_id.exists' => 'Selected existing customer does not exist.',
            'client_name.required_if' => 'Client full name is required for new client (வாடிக்கையாளர் பெயர் அவசியம்).',
            'client_name.required_without' => 'Client full name is required for new client (வாடிக்கையாளர் பெயர் அவசியம்).',
            'client_phone.required_if' => 'Client phone number is required for new client (தொலைபேசி எண் அவசியம்).',
            'client_phone.required_without' => 'Client phone number is required for new client (தொலைபேசி எண் அவசியம்).',
            'client_phone.unique' => 'Client phone number has already been taken (தொலைபேசி எண் ஏற்கனவே உள்ளது).',
        ];
    }
}
