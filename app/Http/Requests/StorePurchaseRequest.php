<?php

namespace App\Http\Requests;

use App\Models\Material;
use App\Models\ProjectSite;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequest extends FormRequest
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
        if ($this->has('po_number')) {
            $po = trim((string) $this->po_number);
            $this->merge(['po_number' => $po !== '' ? $po : null]);
        }

        if ($this->has('order_date')) {
            $od = trim((string) $this->order_date);
            $this->merge(['order_date' => $od !== '' ? $od : null]);
        }

        if (! $this->filled('project_site_id') && $this->filled('site_name')) {
            $site = ProjectSite::where('name', trim((string) $this->site_name))->first();
            if ($site) {
                $this->merge(['project_site_id' => $site->id]);
            }
        } elseif ($this->filled('project_site_id')) {
            $this->merge(['project_site_id' => (int) $this->project_site_id]);
        }

        if ($this->filled('supplier_id')) {
            $this->merge(['supplier_id' => (int) $this->supplier_id]);
            if (! $this->filled('supplier_name')) {
                $supplier = Supplier::find($this->supplier_id);
                if ($supplier) {
                    $this->merge(['supplier_name' => $supplier->name]);
                }
            }
        } elseif ($this->filled('supplier_name')) {
            $supplier = Supplier::where('name', trim((string) $this->supplier_name))->first();
            if ($supplier) {
                $this->merge(['supplier_id' => $supplier->id]);
            }
        }

        if ($this->has('status_reason')) {
            $reason = trim((string) $this->status_reason);
            $this->merge(['status_reason' => $reason !== '' ? $reason : null]);
        }

        if ($this->has('notes')) {
            $notes = trim((string) $this->notes);
            $this->merge(['notes' => $notes !== '' ? $notes : null]);
        }

        if ($this->has('items') && is_string($this->items)) {
            $decoded = json_decode($this->items, true);
            if (is_array($decoded)) {
                $this->merge(['items' => $decoded]);
            }
        }

        if ($this->has('items') && is_array($this->items)) {
            $cleaned = [];
            foreach ($this->items as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $qty = isset($item['quantity']) && $item['quantity'] !== '' && is_numeric($item['quantity'])
                    ? (float) $item['quantity']
                    : (isset($item['qty']) && $item['qty'] !== '' && is_numeric($item['qty']) ? (float) $item['qty'] : null);

                $rate = isset($item['unit_price']) && is_numeric($item['unit_price'])
                    ? (float) $item['unit_price']
                    : (isset($item['rate']) && is_numeric($item['rate']) ? (float) $item['rate'] : 0);

                $matId = ! empty($item['material_id']) ? (int) $item['material_id'] : null;
                $matName = ! empty($item['material_name']) ? trim((string) $item['material_name']) : (! empty($item['item_name']) ? trim((string) $item['item_name']) : null);
                $unitVal = ! empty($item['unit']) ? trim((string) $item['unit']) : null;

                if (! $matId && ! empty($matName) && empty($item['warehouse_item_id'])) {
                    $mat = Material::where('name_en', $matName)->first();
                    if (! $mat) {
                        $uModel = null;
                        if ($unitVal) {
                            $uModel = Unit::where('name_en', $unitVal)->orWhere('code', $unitVal)->first();
                        }
                        if (! $uModel) {
                            $uModel = Unit::where('status', 'Active')->first();
                        }
                        $mat = Material::create([
                            'name_en' => $matName,
                            'name_ta' => $matName,
                            'unit_id' => $uModel?->id,
                            'status' => 'Active',
                        ]);
                    }
                    $matId = $mat->id;
                    if (! $unitVal && $mat->unit) {
                        $unitVal = $mat->unit->name_en;
                    }
                }

                $cleaned[] = [
                    'material_id' => $matId,
                    'warehouse_item_id' => ! empty($item['warehouse_item_id']) ? (int) $item['warehouse_item_id'] : null,
                    'material_name' => $matName,
                    'quantity' => $qty,
                    'unit' => $unitVal,
                    'unit_price' => $rate,
                    'total_price' => isset($item['total_price']) && is_numeric($item['total_price']) ? (float) $item['total_price'] : ($qty !== null ? round($qty * $rate, 2) : 0),
                ];
            }
            $this->merge(['items' => $cleaned]);
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
            'po_number' => ['nullable', 'string', 'max:100', Rule::unique('purchase_orders', 'po_number')],
            'order_date' => ['required', 'date', 'before_or_equal:today'],
            'project_site_id' => ['required_without:site_name', 'nullable', 'integer', 'exists:project_sites,id'],
            'site_name' => ['nullable', 'string', 'max:255'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'supplier_name' => ['required', 'string', 'max:255'],
            'source_type' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'string', 'in:Ordered,Order Received,Partial Received,Order Completed,Order Return,Order Canceled'],
            'status_reason' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn () => in_array($this->input('status'), ['Order Return', 'Order Canceled'], true)),
            ],
            'delivery_date' => ['nullable', 'date'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.material_id' => ['required_without:items.*.warehouse_item_id', 'nullable', 'integer', 'exists:materials,id'],
            'items.*.warehouse_item_id' => ['nullable', 'integer', 'exists:warehouse_items,id'],
            'items.*.material_name' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit' => ['required', 'string', 'max:50'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.total_price' => ['nullable', 'numeric', 'min:0'],
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
            'po_number.unique' => 'This PO / Invoice number already exists (இந்த கொள்முதல் ஆணை எண் ஏற்கனவே உள்ளது).',
            'po_number.max' => 'PO number cannot exceed 100 characters (கொள்முதல் ஆணை எண் 100 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'order_date.required' => 'Please select a purchase date (கொள்முதல் தேதியை தேர்வு செய்க).',
            'order_date.before_or_equal' => 'Purchase date cannot be a future date (தேதி எதிர்காலமாக இருக்க முடியாது).',
            'order_date.date' => 'Please enter a valid purchase date (சரியான கொள்முதல் தேதியை உள்ளிடவும்).',
            'project_site_id.required_without' => 'Please select a project site (திட்ட தளத்தை தேர்வு செய்க).',
            'project_site_id.exists' => 'Selected project site does not exist (தேர்ந்தெடுக்கப்பட்ட திட்ட தளம் தவறானது).',
            'project_site_id.integer' => 'Selected project site ID must be valid (சரியான திட்ட தளம் தேர்வு செய்க).',
            'supplier_name.required' => 'Please select a supplier (சப்ளையரை தேர்வு செய்க).',
            'supplier_name.max' => 'Supplier name cannot exceed 255 characters (சப்ளையர் பெயர் 255 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'supplier_id.exists' => 'Selected supplier does not exist (தேர்ந்தெடுக்கப்பட்ட சப்ளையர் தவறானது).',
            'status.required' => 'Please select purchase order status (கொள்முதல் நிலை தேர்வு செய்க).',
            'status.in' => 'Selected status is invalid (தேர்ந்தெடுக்கப்பட்ட நிலை தவறானது).',
            'status_reason.required' => 'Please enter reason for return or cancellation (காரணம் குறிப்பிடவும்).',
            'status_reason.max' => 'Reason cannot exceed 255 characters (காரணம் 255 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'delivery_date.date' => 'Delivery date must be a valid date (டெலிவரி தேதி சரியான தேதியாக இருக்க வேண்டும்).',
            'total_amount.numeric' => 'Total amount must be a number (மொத்த தொகை எண்ணாக இருக்க வேண்டும்).',
            'total_amount.min' => 'Total amount cannot be negative (மொத்த தொகை எதிர்மறையாக இருக்கக்கூடாது).',
            'notes.max' => 'Notes cannot exceed 1000 characters (குறிப்புகள் 1000 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'items.required' => 'At least one material item is required (குறைந்தது ஒரு பொருள் தேவை).',
            'items.array' => 'Material items list must be valid (பொருட்கள் பட்டியல் சரியானதாக இருக்க வேண்டும்).',
            'items.min' => 'At least one material item is required (குறைந்தது ஒரு பொருள் தேவை).',
            'items.*.material_id.required_without' => 'Please select a material (பொருளை தேர்வு செய்க).',
            'items.*.material_id.exists' => 'Selected material does not exist (தேர்ந்தெடுக்கப்பட்ட பொருள் தவறானது).',
            'items.*.quantity.required' => 'Quantity is required (அளவு அவசியம்).',
            'items.*.quantity.numeric' => 'Quantity must be a valid number (அளவு சரியான எண்ணாக இருக்க வேண்டும்).',
            'items.*.quantity.gt' => 'Quantity must be greater than zero (அளவு 0-க்கு மேல் இருக்க வேண்டும்).',
            'items.*.unit.required' => 'Unit of measurement is required (அளவீட்டு அலகு அவசியம்).',
            'items.*.unit.max' => 'Unit cannot exceed 50 characters (அளவீட்டு அலகு 50 எழுத்துகளுக்குள் இருக்க வேண்டும்).',
            'items.*.unit_price.numeric' => 'Unit price must be a number (விலை எண்ணாக இருக்க வேண்டும்).',
            'items.*.unit_price.min' => 'Unit price cannot be negative (விலை எதிர்மறையாக இருக்கக்கூடாது).',
        ];
    }
}
