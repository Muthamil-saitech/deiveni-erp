<?php

namespace App\Http\Requests;

use App\Models\Unit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreWarehouseRequest extends FormRequest
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
        if ($this->has('sku') && filled($this->sku)) {
            $this->merge([
                'sku' => strtoupper(trim((string) $this->sku)),
            ]);
        }

        if ($this->has('name_en')) {
            $this->merge(['name_en' => trim((string) $this->name_en)]);
        }

        if ($this->has('name_ta')) {
            $this->merge(['name_ta' => trim((string) $this->name_ta)]);
        }

        if ($this->has('category')) {
            $this->merge(['category' => trim((string) $this->category)]);
        }

        if ($this->has('unit_id') && filled($this->unit_id)) {
            $this->merge(['unit_id' => (int) $this->unit_id]);
        } elseif ($this->has('unit') && filled($this->unit)) {
            $val = trim((string) $this->unit);
            if (is_numeric($val)) {
                $this->merge([
                    'unit_id' => (int) $val,
                    'unit' => (int) $val,
                ]);
            } else {
                $matched = Unit::query()
                    ->whereRaw('LOWER(code) = ?', [strtolower($val)])
                    ->orWhereRaw('LOWER(name_en) = ?', [strtolower($val)])
                    ->orWhere('name_en', 'like', "%{$val}%")
                    ->first();

                if (! $matched) {
                    $matched = Unit::first();
                }

                if ($matched) {
                    $this->merge([
                        'unit_id' => $matched->id,
                        'unit' => $matched->id,
                    ]);
                }
            }
        }

        if ($this->filled('cash_amount') && ! $this->filled('unit_rate')) {
            $this->merge(['unit_rate' => $this->input('cash_amount')]);
        } elseif ($this->filled('unit_rate') && ! $this->filled('cash_amount')) {
            $this->merge(['cash_amount' => $this->input('unit_rate')]);
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
            'sku' => ['required', 'string', 'max:50', Rule::unique('warehouse_items', 'sku')],
            'name_en' => ['required', 'string', 'max:255'],
            'name_ta' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'unit' => ['nullable'],
            'current_stock' => ['required', 'numeric', 'min:0'],
            'min_threshold' => ['required', 'numeric', 'min:0'],
            'cash_amount' => ['required', 'numeric', 'min:0'],
            'unit_rate' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'in:Active,Inactive'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->has('unit_id') && ! $validator->errors()->has('unit')) {
                $validator->errors()->add('unit', $validator->errors()->first('unit_id'));
            }
        });
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sku.required' => 'SKU / Item code is required (SKU குறியீடு அவசியம்).',
            'sku.string' => 'SKU / Item code must be a valid text (SKU குறியீடு சரியான எழுத்தாக இருக்க வேண்டும்).',
            'sku.max' => 'SKU / Item code cannot exceed 50 characters (SKU குறியீடு 50 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'sku.unique' => 'This SKU / Item code already exists (இந்த SKU குறியீடு ஏற்கனவே உள்ளது).',

            'category.required' => 'Please select a category (பிரிவை தேர்வு செய்க).',
            'category.string' => 'Category must be a valid text (பிரிவு சரியான எழுத்தாக இருக்க வேண்டும்).',
            'category.max' => 'Category cannot exceed 100 characters (பிரிவு 100 எழுத்துகளுக்குள் இருக்க வேண்டும்).',

            'name_en.required' => 'Item name in English is required (பொருளின் ஆங்கிலப் பெயர் அவசியம்).',
            'name_en.string' => 'Item name in English must be a valid text (ஆங்கிலப் பெயர் சரியான எழுத்தாக இருக்க வேண்டும்).',
            'name_en.max' => 'Item name in English cannot exceed 255 characters (ஆங்கிலப் பெயர் 255 எழுத்துகளுக்குள் இருக்க வேண்டும்).',

            'name_ta.required' => 'Item name in Tamil is required (பொருளின் தமிழ் பெயர் அவசியம்).',
            'name_ta.string' => 'Item name in Tamil must be a valid text (தமிழ் பெயர் சரியான எழுத்தாக இருக்க வேண்டும்).',
            'name_ta.max' => 'Item name in Tamil cannot exceed 255 characters (தமிழ் பெயர் 255 எழுத்துகளுக்குள் இருக்க வேண்டும்).',

            'unit_id.required' => 'Unit of measure is required (அளவீட்டு அலகு அவசியம்).',
            'unit_id.integer' => 'Unit of measure must be a valid unit (அளவீட்டு அலகு சரியாக இருக்க வேண்டும்).',
            'unit_id.exists' => 'Selected unit does not exist (தேர்ந்தெடுக்கப்பட்ட அலகு செல்லாது).',
            'unit.required' => 'Unit of measure is required (அளவீட்டு அலகு அவசியம்).',
            'unit.string' => 'Unit of measure must be a valid text (அளவீட்டு அலகு சரியான எழுத்தாக இருக்க வேண்டும்).',
            'unit.max' => 'Unit of measure cannot exceed 50 characters (அளவீட்டு அலகு 50 எழுத்துகளுக்குள் இருக்க வேண்டும்).',

            'current_stock.required' => 'Initial stock is required (இருப்பு அளவு அவசியம்).',
            'current_stock.numeric' => 'Initial stock must be a number (இருப்பு எண் வடிவில் இருக்க வேண்டும்).',
            'current_stock.min' => 'Initial stock must be 0 or more (இருப்பு 0 அல்லது அதற்கு மேல் இருக்க வேண்டும்).',

            'unit.required' => 'Unit of measure is required (அளவீட்டு அலகு அவசியம்).',
            'unit.string' => 'Unit of measure must be a valid text (அளவீட்டு அலகு சரியான எழுத்தாக இருக்க வேண்டும்).',
            'unit.max' => 'Unit of measure cannot exceed 50 characters (அளவீட்டு அலகு 50 எழுத்துகளுக்குள் இருக்க வேண்டும்).',

            'min_threshold.required' => 'Minimum threshold is required (குறைந்தபட்ச இருப்பு அளவு அவசியம்).',
            'min_threshold.numeric' => 'Minimum threshold must be a number (குறைந்தபட்ச இருப்பு எண் வடிவில் இருக்க வேண்டும்).',
            'min_threshold.min' => 'Minimum threshold must be 0 or more (குறைந்தபட்ச இருப்பு 0 அல்லது அதற்கு மேல் இருக்க வேண்டும்).',

            'cash_amount.required' => 'Cash amount / unit price is required (விலை / அலகுத் தொகை அவசியம்).',
            'cash_amount.numeric' => 'Cash amount must be a number (விலை எண் வடிவில் இருக்க வேண்டும்).',
            'cash_amount.min' => 'Cash amount must be 0 or more (விலை 0 அல்லது அதற்கு மேல் இருக்க வேண்டும்).',

            'unit_rate.numeric' => 'Unit rate must be a number (அலகு விலை எண் வடிவில் இருக்க வேண்டும்).',
            'unit_rate.min' => 'Unit rate must be 0 or more (அலகு விலை 0 அல்லது அதற்கு மேல் இருக்க வேண்டும்).',

            'status.in' => 'Selected status must be Active or Inactive (நிலை Active அல்லது Inactive ஆக இருக்க வேண்டும்).',
        ];
    }
}
