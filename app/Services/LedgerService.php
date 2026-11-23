<?php

namespace App\Services;

use App\Models\Bank;
use App\Models\Branch;
use App\Models\DailyAllocation;
use App\Models\DailyAllocationItem;
use App\Models\Ledger;
use App\Models\ProjectSite;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\SiteCash;
use App\Models\Supplier;
use App\Models\TransactionType;
use App\Repositories\Contracts\LedgerRepositoryInterface;
use App\Services\Contracts\LedgerServiceInterface;
use App\Services\Contracts\SiteCashServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LedgerService implements LedgerServiceInterface
{
    public function __construct(
        public LedgerRepositoryInterface $ledgerRepository,
        public SiteCashServiceInterface $siteCashService
    ) {}

    /**
     * Retrieve all ledger records matching optional filters.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Ledger>
     */
    public function getAllLedgers(array $filters = []): Collection
    {
        return $this->ledgerRepository->all($filters);
    }

    /**
     * Find a ledger record by ID with relations.
     */
    public function getLedgerById(int $id): ?Ledger
    {
        return $this->ledgerRepository->findById($id);
    }

    /**
     * Create a new ledger entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function createLedger(array $data): Ledger
    {
        $payload = $this->preparePayload($data);

        $ledger = $this->ledgerRepository->create($payload);

        $this->syncAccountManagerWagePayment($ledger, $data);

        return $ledger;
    }

    /**
     * Update an existing ledger entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateLedger(int $id, array $data): Ledger
    {
        $ledger = $this->ledgerRepository->findById($id);

        if (! $ledger) {
            throw new \InvalidArgumentException("Ledger entry with ID {$id} not found.");
        }

        $payload = $this->preparePayload($data, $ledger);

        $updated = $this->ledgerRepository->update($ledger, $payload);

        $this->syncAccountManagerWagePayment($updated, $data);

        return $updated;
    }

    /**
     * Delete a ledger entry by ID.
     */
    public function deleteLedger(int $id): bool
    {
        $ledger = $this->ledgerRepository->findById($id);

        if (! $ledger) {
            return false;
        }

        if (! empty($ledger->receipt_image)) {
            $this->deleteReceiptImage($ledger->receipt_image);
        }

        // Delete any extra helper worker line items created specifically by this ledger
        $extraItemAllocIds = DailyAllocationItem::where('ledger_id', $ledger->id)
            ->where('category_code', 'EXTRA-HELPER')
            ->pluck('daily_allocation_id')
            ->unique();

        DailyAllocationItem::where('ledger_id', $ledger->id)
            ->where('category_code', 'EXTRA-HELPER')
            ->delete();

        // Revert any standard daily allocation items marked paid by this ledger
        DailyAllocationItem::where('ledger_id', $ledger->id)->update([
            'paid_by' => null,
            'wage_cash_source' => null,
            'ledger_id' => null,
            'paid_at' => null,
        ]);

        foreach ($extraItemAllocIds as $eAllocId) {
            $eAlloc = DailyAllocation::find($eAllocId);
            if ($eAlloc) {
                $count = (int) $eAlloc->items()->sum('worker_count');
                $wage = (float) $eAlloc->items()->sum('subtotal');
                $eAlloc->update([
                    'total_labour_count' => $count,
                    'total_labour_wage' => $wage,
                    'grand_total' => $wage + (float) $eAlloc->total_expenses,
                ]);
            }
        }

        // Delete associated SiteCash record if any
        $siteId = $ledger->project_site_id;
        SiteCash::where('ledger_id', $ledger->id)->delete();
        if ($siteId) {
            $this->siteCashService->recalculateSiteSpent($siteId);
        }

        return $this->ledgerRepository->delete($ledger);
    }

    /**
     * Get ledger financial summary metrics.
     *
     * @return array{total_income: float, total_expense: float, closing_balance: float, opening_balance: float, yesterday_profit: float, yesterday_date: string}
     */
    public function getSummary(): array
    {
        return $this->ledgerRepository->getTotals();
    }

    /**
     * Compute chronological running balances for ledger entries.
     *
     * @return array<int, float>
     */
    public function getRunningBalances(): array
    {
        return $this->ledgerRepository->getRunningBalances();
    }

    /**
     * Prepare data payload for create/update.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function preparePayload(array $data, ?Ledger $existing = null): array
    {
        $source = trim((string) ($data['source'] ?? $existing?->source ?? 'Office and other expenses'));
        $flow = in_array($data['flow'] ?? $existing?->flow, ['income', 'expense']) ? $data['flow'] : 'income';
        $amount = (float) ($data['amount'] ?? $existing?->amount ?? 0);
        $entryDate = ! empty($data['entry_date']) ? $data['entry_date'] : ($existing?->entry_date?->format('Y-m-d') ?? now()->format('Y-m-d'));

        // Handle Construction fields (Task 3)
        $projectSiteId = null;
        $siteName = null;
        $clientName = null;
        $cashReceivedFrom = null;
        if ($source === 'Construction' || ! empty($data['project_site_id'])) {
            $projectSiteId = ! empty($data['project_site_id']) ? (int) $data['project_site_id'] : null;
            if ($projectSiteId) {
                $site = ProjectSite::with('client')->find($projectSiteId);
                if ($site) {
                    $siteName = $site->name;
                    $clientName = ! empty($data['client_name']) ? trim((string) $data['client_name']) : $site->client?->name;
                }
            } else {
                $siteName = ! empty($data['site_name']) ? trim((string) $data['site_name']) : null;
                $clientName = ! empty($data['client_name']) ? trim((string) $data['client_name']) : null;
            }

            if ($flow === 'income' && $projectSiteId && in_array($data['cash_received_from'] ?? null, ['client', 'owner'], true)) {
                $cashReceivedFrom = $data['cash_received_from'];
            }
        }

        // Handle Supplier fields (Task 4: only supplier select box)
        $supplierId = null;
        $supplierName = null;
        if ($source === 'Supplier' || ! empty($data['supplier_id']) || ! empty($data['supplier_name'])) {
            $supplierId = ! empty($data['supplier_id']) ? (int) $data['supplier_id'] : null;
            if ($supplierId) {
                $supplier = Supplier::find($supplierId);
                $supplierName = $supplier?->name ?? ($data['supplier_name'] ?? null);
            } else {
                $supplierName = ! empty($data['supplier_name']) ? trim((string) $data['supplier_name']) : null;
                if ($supplierName) {
                    $supp = Supplier::where('name', $supplierName)->first();
                    $supplierId = $supp?->id;
                }
            }
        }

        // Handle Bank Valuation fields
        $bankId = null;
        $bankName = null;
        $branchId = null;
        $branchName = null;
        $bankCustomerRef = null;
        if ($source === 'Bank Valuation' || $source === 'Bank') {
            $bankId = ! empty($data['bank_id']) ? (int) $data['bank_id'] : null;
            if ($bankId) {
                $bank = Bank::find($bankId);
                $bankName = $bank?->bank_name;
            }
            $branchId = ! empty($data['branch_id']) ? (int) $data['branch_id'] : null;
            if ($branchId) {
                $branch = Branch::find($branchId);
                $branchName = $branch?->branch_name;
                if (! $bankName && $branch?->bank_id) {
                    $bank = Bank::find($branch->bank_id);
                    $bankId = $bank?->id;
                    $bankName = $bank?->bank_name;
                }
            }
            $bankCustomerRef = ! empty($data['bank_customer_ref']) ? trim((string) $data['bank_customer_ref']) : null;
        }

        // Handle Rental fields
        $propertyId = null;
        $propertyName = null;
        $propertyTypeId = null;
        $unitName = null;
        if ($source === 'Rental') {
            $propertyId = ! empty($data['property_id']) ? (int) $data['property_id'] : null;
            if ($propertyId) {
                $prop = Property::find($propertyId);
                $propertyName = $prop?->property_name;
            }
            $propertyTypeId = ! empty($data['property_type_id']) ? (int) $data['property_type_id'] : null;
            if ($propertyTypeId) {
                $pt = PropertyType::find($propertyTypeId);
                $unitName = $pt?->shop_home_office_name;
                if (! $propertyName && $pt?->property_id) {
                    $prop = Property::find($pt->property_id);
                    $propertyId = $prop?->id;
                    $propertyName = $prop?->property_name;
                }
            }
        }

        // Office expense details
        $officeExpenseDetails = ! empty($data['office_expense_details']) ? trim((string) $data['office_expense_details']) : null;

        // Relation with Transaction Type Master (Task 5)
        $transactionTypeId = null;
        $transactionTypeName = null;
        $transactionTypeValue = null;
        $transactionTypeData = [];

        // Check if multiple transaction types passed as an associative array [txn_type_id => value]
        if (! empty($data['transaction_types']) && is_array($data['transaction_types'])) {
            foreach ($data['transaction_types'] as $tId => $val) {
                $tId = (int) $tId;
                $val = trim((string) $val);
                if ($tId > 0) {
                    $tt = TransactionType::find($tId);
                    if ($tt) {
                        $transactionTypeData[] = [
                            'id' => $tt->id,
                            'name' => $tt->name_en,
                            'category' => $tt->category,
                            'value' => $val,
                        ];

                        // Use first one with a value (or the first one encountered) as primary relation
                        if ($transactionTypeId === null || (! empty($val) && empty($transactionTypeValue))) {
                            $transactionTypeId = $tt->id;
                            $transactionTypeName = $tt->name_en;
                            $transactionTypeValue = $val;
                        }
                    }
                }
            }
        }

        // Direct single transaction type input fallback
        if (! empty($data['transaction_type_id'])) {
            $transactionTypeId = (int) $data['transaction_type_id'];
            $tt = TransactionType::find($transactionTypeId);
            if ($tt) {
                $transactionTypeName = $tt->name_en;
            }
        }
        if (! empty($data['transaction_type_name']) && empty($transactionTypeName)) {
            $transactionTypeName = trim((string) $data['transaction_type_name']);
        }
        if (isset($data['transaction_type_value']) && empty($transactionTypeValue)) {
            $transactionTypeValue = trim((string) $data['transaction_type_value']);
        }

        // Receipt image handling
        $receiptImage = $existing?->receipt_image;
        if (isset($data['receipt_image'])) {
            if ($data['receipt_image'] instanceof UploadedFile) {
                if ($existing && $existing->receipt_image) {
                    $this->deleteReceiptImage($existing->receipt_image);
                }
                $receiptImage = $this->storeReceiptFile($data['receipt_image']);
            } elseif (is_string($data['receipt_image']) && str_starts_with($data['receipt_image'], 'data:image')) {
                if ($existing && $existing->receipt_image) {
                    $this->deleteReceiptImage($existing->receipt_image);
                }
                $receiptImage = $this->storeReceiptBase64($data['receipt_image']);
            } elseif (empty($data['receipt_image']) && $existing) {
                $this->deleteReceiptImage($existing->receipt_image);
                $receiptImage = null;
            }
        } elseif (isset($data['image']) && is_string($data['image']) && str_starts_with($data['image'], 'data:image')) {
            if ($existing && $existing->receipt_image) {
                $this->deleteReceiptImage($existing->receipt_image);
            }
            $receiptImage = $this->storeReceiptBase64($data['image']);
        }

        // Description fallback / narration
        $description = trim((string) ($data['description'] ?? $data['desc'] ?? $existing?->description ?? ''));
        if (empty($description)) {
            if ($source === 'Construction') {
                $description = 'Construction payment'.($siteName ? " - {$siteName}" : '').($clientName ? " ({$clientName})" : '');
            } elseif ($source === 'Supplier') {
                $description = 'Payment to '.($supplierName ?: 'Supplier');
            } elseif ($source === 'Bank Valuation' || $source === 'Bank') {
                $description = ($bankName ? "{$bankName} " : 'Bank ').($branchName ? "({$branchName})" : '').($bankCustomerRef ? " - {$bankCustomerRef}" : '');
            } elseif ($source === 'Rental') {
                $description = ($propertyName ?: 'Property').($unitName ? " [{$unitName}]" : '').' rent';
            } elseif ($source === 'Office and other expenses') {
                $description = $officeExpenseDetails ?: 'Office expense';
            } else {
                $description = 'Daily ledger entry';
            }
        }

        $isAmWagePayment = ! empty($data['is_am_wage_payment']) && (string) $data['is_am_wage_payment'] === '1';

        return [
            'entry_date' => $entryDate,
            'flow' => $flow,
            'source' => $source,
            'amount' => $amount,
            'project_site_id' => $projectSiteId,
            'site_name' => $siteName,
            'client_name' => $clientName,
            'cash_received_from' => $cashReceivedFrom,
            'supplier_id' => $supplierId,
            'supplier_name' => $supplierName,
            'bank_id' => $bankId,
            'bank_name' => $bankName,
            'branch_id' => $branchId,
            'branch_name' => $branchName,
            'bank_customer_ref' => $bankCustomerRef,
            'property_id' => $propertyId,
            'property_name' => $propertyName,
            'property_type_id' => $propertyTypeId,
            'unit_name' => $unitName,
            'office_expense_details' => $officeExpenseDetails,
            'transaction_type_id' => $transactionTypeId,
            'transaction_type_name' => $transactionTypeName,
            'transaction_type_value' => $transactionTypeValue,
            'transaction_type_data' => ! empty($transactionTypeData) ? $transactionTypeData : null,
            'description' => $description,
            'receipt_image' => $receiptImage,
            'daily_allocation_id' => $isAmWagePayment && ! empty($data['daily_allocation_id']) ? (int) $data['daily_allocation_id'] : ($isAmWagePayment ? ($existing?->daily_allocation_id ?? null) : null),
            'wage_item_ids' => $isAmWagePayment && ! empty($data['wage_item_ids']) ? (array) $data['wage_item_ids'] : ($isAmWagePayment ? ($existing?->wage_item_ids ?? null) : null),
            'extra_workers' => $isAmWagePayment && ! empty($data['extra_workers']) ? (array) $data['extra_workers'] : ($isAmWagePayment ? ($existing?->extra_workers ?? null) : null),
            'wage_cash_source' => $isAmWagePayment && ! empty($data['wage_cash_source']) ? (string) $data['wage_cash_source'] : ($isAmWagePayment ? ($existing?->wage_cash_source ?? 'office_cash') : null),
            'created_by' => Auth::id() ?? ($existing?->created_by ?? null),
        ];
    }

    /**
     * Synchronize painter/electrician wage payment with daily allocation items and site cash.
     *
     * @param  array<string, mixed>  $data
     */
    protected function syncAccountManagerWagePayment(Ledger $ledger, array $data): void
    {
        $isWagePayment = ! empty($data['is_am_wage_payment']) && (string) $data['is_am_wage_payment'] === '1';

        // STRICT ISOLATION:
        // Ordinary site expenses (e.g. piping, materials, local work) recorded by Account Manager
        // MUST NEVER be categorized as Painter/Electrician wages and MUST NEVER deduct from Site Cash!
        if (! $isWagePayment || $ledger->flow !== 'expense' || $ledger->source !== 'Construction' || ! $ledger->project_site_id) {
            SiteCash::where('ledger_id', $ledger->id)->where('transaction_type', 'Daily Wage Payment')->delete();

            return;
        }

        $allocId = (int) ($data['daily_allocation_id'] ?? $ledger->daily_allocation_id ?? 0);
        $wageItemIds = $data['wage_item_ids'] ?? $ledger->wage_item_ids ?? [];

        if (! $allocId) {
            return;
        }

        $alloc = DailyAllocation::find($allocId);
        if (! $alloc) {
            return;
        }

        $siteCashCreator = $alloc?->created_by ?: ($ledger->projectSite?->created_by ?: $ledger->created_by);

        $cashSource = $ledger->wage_cash_source ?: ($data['wage_cash_source'] ?? 'office_cash');

        if ($allocId) {
            $itemQuery = DailyAllocationItem::where('daily_allocation_id', $allocId);
            if (! empty($wageItemIds) && is_array($wageItemIds)) {
                $itemQuery->whereIn('id', $wageItemIds);
            } else {
                $itemQuery->where(function ($q) {
                    $q->whereIn('category_code', ['CAT-007', 'CAT-008', 'CAT-009'])
                        ->orWhere('name_en', 'like', '%Painter%')
                        ->orWhere('name_en', 'like', '%Electrician%')
                        ->orWhere('name_en', 'like', '%Wireman%')
                        ->orWhere('name_ta', 'like', '%பெயிண்டர்%')
                        ->orWhere('name_ta', 'like', '%எலக்ட்ரீஷியன்%');
                });
            }

            $itemQuery->update([
                'paid_by' => 'Account Manager',
                'wage_cash_source' => $cashSource,
                'ledger_id' => $ledger->id,
                'paid_at' => now(),
            ]);

            if (! $ledger->daily_allocation_id) {
                $ledger->update(['daily_allocation_id' => $allocId]);
            }

            // Process Extra / Support Workers brought by Painter / Electrician
            $extraWorkers = $ledger->extra_workers ?? ($data['extra_workers'] ?? []);
            if (! empty($extraWorkers) && is_array($extraWorkers)) {
                DailyAllocationItem::where('ledger_id', $ledger->id)
                    ->where('category_code', 'EXTRA-HELPER')
                    ->delete();

                foreach ($extraWorkers as $ew) {
                    $workerName = trim((string) ($ew['name'] ?? ''));
                    $workerWork = trim((string) ($ew['work'] ?? ''));
                    $workerSalary = (float) ($ew['salary'] ?? 0);

                    if ($workerName !== '' || $workerSalary > 0) {
                        $displayName = $workerWork ? "{$workerName} ({$workerWork})" : $workerName;
                        DailyAllocationItem::create([
                            'daily_allocation_id' => $allocId,
                            'worker_category_id' => null,
                            'category_code' => 'EXTRA-HELPER',
                            'name_ta' => $displayName,
                            'name_en' => $displayName,
                            'daily_wage' => $workerSalary,
                            'master_wage' => $workerSalary,
                            'worker_count' => 1,
                            'subtotal' => $workerSalary,
                            'paid_by' => 'Account Manager',
                            'wage_cash_source' => $cashSource,
                            'ledger_id' => $ledger->id,
                            'paid_at' => now(),
                        ]);
                    }
                }

                if ($alloc) {
                    $count = (int) $alloc->items()->sum('worker_count');
                    $wage = (float) $alloc->items()->sum('subtotal');
                    $alloc->update([
                        'total_labour_count' => $count,
                        'total_labour_wage' => $wage,
                        'grand_total' => $wage + (float) $alloc->total_expenses,
                    ]);
                }
            }
        }

        // Synchronize SiteCash transaction (budget includes it; site cash in hand deducted ONLY if Project Cash)
        $siteCash = SiteCash::where('ledger_id', $ledger->id)->first();
        $entryDateStr = $ledger->entry_date instanceof \DateTimeInterface
            ? $ledger->entry_date->format('Y-m-d')
            : (string) $ledger->entry_date;

        $entryDateDisplay = $ledger->entry_date instanceof \DateTimeInterface
            ? $ledger->entry_date->format('d M Y')
            : $entryDateStr;

        $cashSourceLabel = ($cashSource === 'project_cash') ? 'Project Cash' : 'Office Cash';

        $siteCashData = [
            'project_site_id' => $ledger->project_site_id,
            'transaction_type' => 'Daily Wage Payment',
            'flow_type' => 'outflow',
            'amount' => (float) $ledger->amount,
            'payment_mode' => 'Cash',
            'paid_to' => 'Painter / Electrician',
            'paid_by' => 'Account Manager',
            'wage_cash_source' => $cashSource,
            'extra_workers' => ! empty($extraWorkers) ? $extraWorkers : null,
            'receiver_name' => 'Painter / Electrician',
            'is_expense' => true,
            'in_amount' => 0.00,
            'out_amount' => (float) $ledger->amount,
            'bill_no' => 'AM-WAGE-'.$ledger->id,
            'payment_date' => $entryDateStr,
            'payment_time' => now()->format('h:i A'),
            'description' => str_contains((string) $ledger->description, 'Painter & Electrician daily wages')
                ? "Painter & Electrician daily wages ({$entryDateDisplay})"
                : ($ledger->description ?: "Daily wages for Painter & Electrician on {$entryDateDisplay} (Paid by Account Manager from {$cashSourceLabel})"),
            'ledger_id' => $ledger->id,
            'created_by' => $siteCashCreator,
        ];

        if ($siteCash) {
            $siteCash->update($siteCashData);
        } else {
            SiteCash::create($siteCashData);
        }

        $this->siteCashService->recalculateSiteSpent($ledger->project_site_id);
    }

    /**
     * Store uploaded receipt image file into public/uploads/receipts.
     */
    protected function storeReceiptFile(UploadedFile $file): string
    {
        $destinationDir = public_path('uploads/receipts');
        if (! File::isDirectory($destinationDir)) {
            File::makeDirectory($destinationDir, 0755, true, true);
        }

        $extension = $file->getClientOriginalExtension() ?: 'png';
        $filename = 'ledger_receipt_'.time().'_'.Str::random(8).'.'.$extension;
        $file->move($destinationDir, $filename);

        return 'uploads/receipts/'.$filename;
    }

    /**
     * Store base64 data image into public/uploads/receipts.
     */
    protected function storeReceiptBase64(string $base64Data): string
    {
        $destinationDir = public_path('uploads/receipts');
        if (! File::isDirectory($destinationDir)) {
            File::makeDirectory($destinationDir, 0755, true, true);
        }

        $extension = 'png';
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
            $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
            $type = strtolower($type[1]);
            if (in_array($type, ['jpeg', 'jpg', 'png', 'webp'])) {
                $extension = $type;
            }
        }

        $decoded = base64_decode($base64Data);
        $filename = 'ledger_receipt_'.time().'_'.Str::random(8).'.'.$extension;
        File::put($destinationDir.DIRECTORY_SEPARATOR.$filename, $decoded);

        return 'uploads/receipts/'.$filename;
    }

    /**
     * Delete local receipt image file.
     */
    protected function deleteReceiptImage(string $path): void
    {
        $fullPath = public_path($path);
        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }
    }
}
