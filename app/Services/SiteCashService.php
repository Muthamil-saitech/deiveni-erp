<?php

namespace App\Services;

use App\Models\CashTransfer;
use App\Models\DailyAllocationItem;
use App\Models\Ledger;
use App\Models\ProjectSite;
use App\Models\SiteCash;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WarehouseItem;
use App\Models\WarehouseMovement;
use App\Repositories\Contracts\SiteCashRepositoryInterface;
use App\Services\Contracts\SiteCashServiceInterface;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SiteCashService implements SiteCashServiceInterface
{
    public function __construct(
        public SiteCashRepositoryInterface $siteCashRepository
    ) {}

    /**
     * Retrieve all site cash records.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, SiteCash>
     */
    public function getAllTransactions(array $filters = []): Collection
    {
        return $this->siteCashRepository->all($filters);
    }

    /**
     * Find a site cash record by ID.
     */
    public function getTransactionById(int $id): ?SiteCash
    {
        return $this->siteCashRepository->findById($id);
    }

    /**
     * Create a new site cash transaction record and update the site's spent column.
     *
     * @param  array<string, mixed>  $data
     */
    public function createTransaction(array $data): SiteCash
    {
        return DB::transaction(function () use ($data) {
            $formattedData = $this->prepareTransactionData($data);
            if (! empty($formattedData['project_site_id'])) {
                $this->ensureSiteManagerOwnsSite((int) $formattedData['project_site_id']);
            }

            $siteCash = $this->siteCashRepository->create($formattedData);

            // Handle warehouse stock deductions if sourced from warehouse
            if (! empty($formattedData['items']) && is_array($formattedData['items'])) {
                $this->handleWarehouseDeductions($siteCash, $formattedData['items']);
            }

            // Link returned wage transactions if this is a Balance Return
            if (! empty($siteCash->returned_wage_ids) && is_array($siteCash->returned_wage_ids)) {
                SiteCash::whereIn('id', $siteCash->returned_wage_ids)
                    ->update(['return_site_cash_id' => $siteCash->id]);
            }

            // Mark selected DailyAllocationItem rows as paid by Site Manager & refer removed items to Account Manager
            if (in_array($siteCash->transaction_type, ['Daily Wage Payment', 'Daily Worker Wage Payment'], true)
                && ! empty($data['paid_wage_item_ids'])) {
                $paidItemIds = is_string($data['paid_wage_item_ids'])
                    ? json_decode($data['paid_wage_item_ids'], true)
                    : $data['paid_wage_item_ids'];

                if (is_array($paidItemIds) && count($paidItemIds) > 0) {
                    $paidIds = array_values(array_unique(array_map('intval', $paidItemIds)));
                    $allocId = DailyAllocationItem::whereIn('id', $paidIds)->value('daily_allocation_id');

                    DailyAllocationItem::whereIn('id', $paidIds)->update([
                        'paid_by' => 'Site Manager',
                        'site_cash_id' => $siteCash->id,
                        'paid_at' => now(),
                        'referred_to_am' => false,
                    ]);

                    if ($allocId) {
                        DailyAllocationItem::where('daily_allocation_id', $allocId)
                            ->whereNotIn('id', $paidIds)
                            ->whereNull('paid_by')
                            ->update(['referred_to_am' => true]);
                    }
                }
            }

            // Synchronize Balance Return to Account Manager's Ledger as Inflow (+)
            if ($siteCash->transaction_type === 'Balance Returned' && $siteCash->paid_to === 'Account Manager') {
                $this->syncBalanceReturnToLedger($siteCash);
            }

            // Link and settle Cash Transfer if this return is for a Cash Transfer
            if ($siteCash->cash_transfer_id && $siteCash->transaction_type === 'Balance Returned') {
                $transfer = CashTransfer::find($siteCash->cash_transfer_id);
                if ($transfer) {
                    $transfer->update([
                        'settlement_status' => 'settled',
                        'settled_at' => now(),
                        'settled_by' => Auth::id() ?? $siteCash->created_by,
                        'return_site_cash_id' => $siteCash->id,
                        'return_ledger_id' => $siteCash->ledger_id,
                        'settlement_notes' => "Settled via Site Cash Return #{$siteCash->id} on ".now()->format('d M Y'),
                    ]);
                }
            }

            // Task 3: Recalculate expense sum and update the project_site spent column
            if (! empty($siteCash->project_site_id)) {
                $this->recalculateSiteSpent((int) $siteCash->project_site_id);
            }

            return $siteCash;
        });
    }

    /**
     * Deduct inventory stock and record movements when site cash purchases from warehouse.
     *
     * @param  array<int, mixed>  $rawItems
     */
    protected function handleWarehouseDeductions(SiteCash $siteCash, array $rawItems): void
    {
        $isWarehouse = ($siteCash->supplier_name === 'Central Warehouse'
            || $siteCash->supplier_name === 'Own Warehouse'
            || str_contains(strtolower((string) $siteCash->description), 'warehouse'));

        foreach ($rawItems as $item) {
            $itemId = ! empty($item['warehouse_item_id']) ? (int) $item['warehouse_item_id'] : null;
            $qty = (float) ($item['quantity'] ?? ($item['qty'] ?? 0));
            if ($qty <= 0) {
                continue;
            }

            if (! $itemId && $isWarehouse && ! empty($item['name'])) {
                $whItem = WarehouseItem::where('name_en', $item['name'])->orWhere('sku', $item['name'])->first();
                $itemId = $whItem?->id;
            }

            if ($itemId) {
                $wh = WarehouseItem::find($itemId);
                if ($wh) {
                    $wh->current_stock = max(0, (float) $wh->current_stock - $qty);
                    $wh->save();

                    $movementCashAmount = (float) ($item['rate'] ?? ($wh->cash_amount ?: $wh->unit_rate));

                    WarehouseMovement::create([
                        'warehouse_item_id' => $wh->id,
                        'movement_type' => 'Issue to Site',
                        'quantity' => $qty,
                        'cash_amount' => $movementCashAmount,
                        'project_site_id' => $siteCash->project_site_id,
                        'site_name' => $siteCash->projectSite?->name,
                        'reference_no' => $siteCash->bill_no ?: "SC-{$siteCash->id}",
                        'movement_date' => $siteCash->payment_date,
                        'notes' => "Issued via Site Cash Transaction #{$siteCash->id}",
                        'created_by' => $siteCash->created_by,
                    ]);
                }
            }
        }
    }

    /**
     * Synchronize a Balance Return transaction into Account Manager's Daily Ledger as Inflow (+).
     */
    public function syncBalanceReturnToLedger(SiteCash $siteCash): void
    {
        $site = $siteCash->projectSite;
        $clientName = $site?->client?->name ?? 'Site Manager';

        $entryDateStr = $siteCash->payment_date instanceof \DateTimeInterface
            ? $siteCash->payment_date->format('Y-m-d')
            : (string) ($siteCash->payment_date ?: now()->toDateString());

        $entryDateDisplay = $siteCash->payment_date instanceof \DateTimeInterface
            ? $siteCash->payment_date->format('d M Y')
            : Carbon::parse($entryDateStr)->format('d M Y');

        $siteName = $site?->name ?? 'Site';
        $ct = $siteCash->cashTransfer;
        $desc = $siteCash->description;

        if (empty($desc)) {
            if ($ct) {
                $desc = "Balance returned to Account Manager for Cash Transfer #CTR-{$ct->id} ({$ct->purpose}) on {$entryDateDisplay} [Received]";
            } else {
                $desc = "Balance returned to Account Manager for Painter & Electrician wages on {$entryDateDisplay} (Office Cash) [Received]";
            }
        }

        $txnTypeName = $ct ? 'Balance Returned (Cash Transfer)' : 'Balance Returned';

        $transferCreator = $ct?->created_by;
        $amUser = User::where('role', 'account_manager')->first();
        $ledgerCreatorId = $transferCreator ?: ($amUser?->id ?? $siteCash->created_by);

        $ledgerData = [
            'entry_date' => $entryDateStr,
            'flow' => 'income',
            'source' => 'Construction',
            'amount' => (float) $siteCash->amount,
            'project_site_id' => $siteCash->project_site_id,
            'cash_transfer_id' => $siteCash->cash_transfer_id,
            'site_name' => $siteName,
            'client_name' => $clientName,
            'transaction_type_name' => $txnTypeName,
            'description' => $desc,
            'wage_cash_source' => $siteCash->wage_cash_source ?? ($ct ? 'cash_transfer' : 'office_cash'),
            'created_by' => $ledgerCreatorId,
        ];

        if ($siteCash->ledger_id && ($existing = Ledger::find($siteCash->ledger_id))) {
            $existing->update($ledgerData);
        } else {
            $ledger = Ledger::create($ledgerData);
            $siteCash->updateQuietly(['ledger_id' => $ledger->id]);
        }
    }

    /**
     * Update an existing site cash transaction record and update the site's spent column.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateTransaction(int $id, array $data): SiteCash
    {
        return DB::transaction(function () use ($id, $data) {
            $siteCash = $this->siteCashRepository->findById($id);

            if (! $siteCash) {
                throw new \InvalidArgumentException("Site cash transaction #{$id} not found.");
            }

            $oldSiteId = (int) $siteCash->project_site_id;
            $formattedData = $this->prepareTransactionData($data, $siteCash);
            if (! empty($formattedData['project_site_id'])) {
                $this->ensureSiteManagerOwnsSite((int) $formattedData['project_site_id']);
            }

            $updated = $this->siteCashRepository->update($siteCash, $formattedData);

            $newSiteId = (int) $updated->project_site_id;

            // Recalculate for both old and new site if site was changed
            if ($oldSiteId !== $newSiteId && $oldSiteId > 0) {
                $this->recalculateSiteSpent($oldSiteId);
            }

            if ($newSiteId > 0) {
                $this->recalculateSiteSpent($newSiteId);
            }

            // Relink returned wage transactions if returned_wage_ids changed
            if ($updated->transaction_type === 'Balance Returned' && ! empty($updated->returned_wage_ids) && is_array($updated->returned_wage_ids)) {
                SiteCash::where('return_site_cash_id', $updated->id)
                    ->whereNotIn('id', $updated->returned_wage_ids)
                    ->update(['return_site_cash_id' => null]);

                SiteCash::whereIn('id', $updated->returned_wage_ids)
                    ->update(['return_site_cash_id' => $updated->id]);
            }

            // Synchronize Balance Return to Account Manager's Ledger as Inflow (+)
            if ($updated->transaction_type === 'Balance Returned' && $updated->paid_to === 'Account Manager') {
                $this->syncBalanceReturnToLedger($updated);
            }

            return $updated;
        });
    }

    /**
     * Delete a site cash transaction record and update the site's spent column.
     */
    public function deleteTransaction(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $siteCash = $this->siteCashRepository->findById($id);

            if (! $siteCash) {
                return false;
            }

            $siteId = (int) $siteCash->project_site_id;

            // Unlink any wage transactions that were marked returned by this record
            SiteCash::where('return_site_cash_id', $siteCash->id)
                ->update(['return_site_cash_id' => null]);

            // Reset any DailyAllocationItem records paid by this SiteCash entry
            DailyAllocationItem::where('site_cash_id', $siteCash->id)->update([
                'paid_by' => null,
                'site_cash_id' => null,
                'paid_at' => null,
            ]);

            // Delete synced balance return ledger entry if present
            if ($siteCash->transaction_type === 'Balance Returned' && $siteCash->ledger_id) {
                Ledger::where('id', $siteCash->ledger_id)->delete();
            }

            // Remove receipt image if stored locally
            if (! empty($siteCash->receipt_image)) {
                $this->deleteReceiptImage($siteCash->receipt_image);
            }

            $deleted = $this->siteCashRepository->delete($siteCash);

            // Recalculate spent for the site
            if ($deleted && $siteId > 0) {
                $this->recalculateSiteSpent($siteId);
            }

            return $deleted;
        });
    }

    /**
     * Get aggregate cash statistics.
     *
     * @return array<string, float>
     */
    public function getCashStats(?int $projectSiteId = null): array
    {
        return $this->siteCashRepository->getStats($projectSiteId);
    }

    /**
     * Prevent Site Managers from writing transactions against another user's site.
     */
    protected function ensureSiteManagerOwnsSite(int $projectSiteId): void
    {
        $user = Auth::user();

        if ($user?->isSiteManager() && ! ProjectSite::query()
            ->whereKey($projectSiteId)
            ->where('created_by', $user->id)
            ->exists()) {
            throw new AuthorizationException('You are not authorized to use this project site.');
        }
    }

    /**
     * Recalculate and update the "spent" column in project_sites table for a site.
     * Only sums transactions where is_expense = true (i.e. site expenses).
     */
    public function recalculateSiteSpent(int $projectSiteId): float
    {
        $totalExpense = $this->siteCashRepository->getExpensesSumForSite($projectSiteId);

        ProjectSite::query()->where('id', $projectSiteId)->update([
            'spent' => $totalExpense,
        ]);

        return $totalExpense;
    }

    /**
     * Format and normalize payload for site cash transactions.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepareTransactionData(array $data, ?SiteCash $existing = null): array
    {
        $type = trim((string) ($data['transaction_type'] ?? ''));
        $amount = (float) ($data['amount'] ?? 0);

        // If transaction originated from Account Manager, amount cannot be changed
        if ($existing && $existing->isFromAccountManager()) {
            $amount = (float) $existing->amount;
        }

        // Classify inflow vs outflow vs expense
        $inflowTypes = ['Owner Advance', 'Advance Received', 'Client Advance', 'Client Collection', 'Project Cash Received'];
        $returnTypes = ['Balance Returned', 'Balance Returned to AM', 'Balance Returned to Account Manager'];

        $isInflow = in_array($type, $inflowTypes, true) || ($existing && $existing->flow_type === 'inflow' && empty($type));
        $isReturn = in_array($type, $returnTypes, true);

        if ($isInflow) {
            $flowType = 'inflow';
            $inAmount = $amount;
            $outAmount = 0.00;
            $isExpense = false;
        } elseif ($isReturn) {
            $flowType = 'outflow';
            $inAmount = 0.00;
            $outAmount = $amount;
            $isExpense = false;
        } else {
            // Outflow expense (Tool Rental, Supplier Payment, Daily Wage Payment, Material Purchase, Other Expense)
            $flowType = 'outflow';
            $inAmount = 0.00;
            $outAmount = $amount;
            $isExpense = true;
        }

        // Project site resolution
        $siteId = ! empty($data['project_site_id']) ? (int) $data['project_site_id'] : null;
        if (! $siteId && ! empty($data['site_name'])) {
            $site = ProjectSite::query()->where('name', trim((string) $data['site_name']))->first();
            if ($site) {
                $siteId = $site->id;
            }
        }

        // Supplier resolution
        $supplierId = ! empty($data['supplier_id']) ? (int) $data['supplier_id'] : null;
        $supplierName = ! empty($data['supplier_name']) ? trim((string) $data['supplier_name']) : null;

        if ($supplierId && ! $supplierName) {
            $supp = Supplier::find($supplierId);
            $supplierName = $supp?->name;
        } elseif (! $supplierId && $supplierName) {
            $supp = Supplier::query()->where('name', $supplierName)->first();
            if ($supp) {
                $supplierId = $supp->id;
            }
        }

        // Payment date and time
        $paymentDate = ! empty($data['payment_date'])
            ? Carbon::parse($data['payment_date'])->format('Y-m-d')
            : ($existing?->payment_date ? Carbon::parse($existing->payment_date)->format('Y-m-d') : Carbon::now()->format('Y-m-d'));

        $paymentTime = ! empty($data['payment_time'])
            ? trim((string) $data['payment_time'])
            : ($existing?->payment_time ?? Carbon::now()->format('h:i A'));

        // Items breakdown
        $items = $data['items'] ?? null;
        if (is_string($items)) {
            $decoded = json_decode($items, true);
            $items = is_array($decoded) ? $decoded : null;
        }

        // Returned wage transaction IDs
        $returnedWageIds = $data['returned_wage_ids'] ?? ($existing?->returned_wage_ids ?? null);
        if (is_string($returnedWageIds)) {
            $decoded = json_decode($returnedWageIds, true);
            $returnedWageIds = is_array($decoded) ? $decoded : null;
        }

        // Receipt image upload
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
        }

        // Payment mode & reference handling
        $paymentMode = ! empty($data['payment_mode']) ? trim((string) $data['payment_mode']) : ($existing?->payment_mode ?? 'Cash');
        $paymentReference = ! empty($data['payment_reference']) ? trim((string) $data['payment_reference']) : ($existing?->payment_reference ?? null);
        $receiverName = ! empty($data['receiver_name']) ? trim((string) $data['receiver_name']) : ($existing?->receiver_name ?? null);
        $bankName = ! empty($data['bank_name']) ? trim((string) $data['bank_name']) : ($existing?->bank_name ?? null);
        $upiId = ! empty($data['upi_id']) ? trim((string) $data['upi_id']) : ($existing?->upi_id ?? null);
        $accountHolderName = ! empty($data['account_holder_name']) ? trim((string) $data['account_holder_name']) : ($existing?->account_holder_name ?? null);

        if ($paymentMode === 'Cash') {
            $paymentReference = null;
            $bankName = null;
            $upiId = null;
            $accountHolderName = null;
        } elseif ($paymentMode === 'Cheque') {
            $upiId = null;
            $accountHolderName = null;
        } elseif ($paymentMode === 'UPI') {
            $bankName = null;
        }

        // Recipient / Handled By
        $paidTo = ! empty($data['paid_to']) ? trim((string) $data['paid_to']) : ($existing?->paid_to ?? 'Site Manager');
        $paidBy = ! empty($data['paid_by']) ? trim((string) $data['paid_by']) : ($existing?->paid_by ?? null);
        if ($existing && $existing->isFromAccountManager()) {
            $paidBy = $existing->paid_by;
        }
        $ledgerId = ! empty($data['ledger_id']) ? (int) $data['ledger_id'] : ($existing?->ledger_id ?? null);
        $weeklyWageId = ! empty($data['weekly_wage_id']) ? (int) $data['weekly_wage_id'] : ($existing?->weekly_wage_id ?? null);
        $cashTransferId = ! empty($data['cash_transfer_id']) ? (int) $data['cash_transfer_id'] : ($existing?->cash_transfer_id ?? null);

        // Fallback description if blank
        $description = ! empty($data['description']) ? trim((string) $data['description']) : null;
        if (! $description) {
            if ($type === 'Supplier Payment' && $supplierName) {
                $billStr = ! empty($data['bill_no']) ? " ({$data['bill_no']})" : '';
                $description = "Supplier Payment to {$supplierName}{$billStr}";
            } else {
                $description = $type;
            }
        }

        return [
            'project_site_id' => $siteId,
            'transaction_type' => $type,
            'flow_type' => $flowType,
            'amount' => $amount,
            'payment_mode' => $paymentMode,
            'payment_reference' => $paymentReference,
            'paid_to' => $paidTo,
            'paid_by' => $paidBy,
            'ledger_id' => $ledgerId,
            'weekly_wage_id' => $weeklyWageId,
            'cash_transfer_id' => $cashTransferId,
            'wage_cash_source' => ! empty($data['wage_cash_source']) ? trim((string) $data['wage_cash_source']) : ($existing?->wage_cash_source ?? null),
            'returned_wage_ids' => $returnedWageIds,
            'receiver_name' => $receiverName,
            'bank_name' => $bankName,
            'upi_id' => $upiId,
            'account_holder_name' => $accountHolderName,
            'in_amount' => $inAmount,
            'out_amount' => $outAmount,
            'is_expense' => $isExpense,
            'supplier_id' => $supplierId,
            'supplier_name' => $supplierName,
            'is_site_based' => array_key_exists('is_site_based', $data) ? (bool) $data['is_site_based'] : ($existing ? (bool) $existing->is_site_based : true),
            'purchase_order_no' => ! empty($data['purchase_order_no']) ? trim((string) $data['purchase_order_no']) : null,
            'bill_no' => ! empty($data['bill_no']) ? trim((string) $data['bill_no']) : null,
            'payment_date' => $paymentDate,
            'payment_time' => $paymentTime,
            'description' => $description,
            'receipt_image' => $receiptImage,
            'items' => $items,
            'created_by' => Auth::id() ?? ($existing?->created_by ?? null),
        ];
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
        $filename = 'receipt_'.time().'_'.Str::random(8).'.'.$extension;
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
        $filename = 'receipt_'.time().'_'.Str::random(8).'.'.$extension;
        File::put($destinationDir.DIRECTORY_SEPARATOR.$filename, $decoded);

        return 'uploads/receipts/'.$filename;
    }

    /**
     * Delete local receipt image file.
     */
    protected function deleteReceiptImage(?string $relativePath): void
    {
        if (! empty($relativePath)) {
            $fullPath = public_path($relativePath);
            if (File::exists($fullPath)) {
                File::delete($fullPath);
            }
        }
    }
}
