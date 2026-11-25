<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\AdminLedger;
use App\Models\CashTransfer;
use App\Models\Ledger;
use App\Models\ProjectSite;
use App\Models\SiteCash;
use App\Models\User;
use App\Repositories\Contracts\CashTransferRepositoryInterface;
use App\Services\Contracts\CashTransferServiceInterface;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CashTransferService implements CashTransferServiceInterface
{
    public function __construct(
        public CashTransferRepositoryInterface $transferRepo
    ) {}

    /**
     * Get cash transfers along with calculated KPI metrics and filters.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getTransferData(array $filters = []): array
    {
        $transfers = $this->transferRepo->getTransfers($filters);

        $totalOfficeToSite = 0.0;
        $totalProjectCash = 0.0;
        $totalSiteToOffice = 0.0;
        $totalPendingReturn = 0.0;
        $totalSettled = 0.0;

        foreach ($transfers as $t) {
            $amt = (float) $t->amount;
            if ($t->transfer_direction === 'office_to_site') {
                $totalOfficeToSite += $amt;
            } elseif ($t->transfer_direction === 'project_cash') {
                $totalProjectCash += $amt;
                $totalSettled += $amt;
            } else {
                $totalSiteToOffice += $amt;
                if ($t->settlement_status === 'pending') {
                    $totalPendingReturn += $amt;
                } else {
                    $totalSettled += $amt;
                }
            }
        }

        $user = Auth::user();
        foreach ($transfers as $t) {
            $t->setAttribute('can_accept', $t->canBeAcceptedBy($user));
        }

        $kpis = [
            'total_office_to_site' => $totalOfficeToSite,
            'total_project_cash' => $totalProjectCash,
            'total_site_to_office' => $totalSiteToOffice,
            'total_pending_return' => $totalPendingReturn,
            'total_settled' => $totalSettled,
            'total_pending_acceptance' => (float) $transfers->where('acceptance_status', 'pending')->sum('amount'),
            'count' => $transfers->count(),
        ];

        return [
            'transfers' => $transfers,
            'kpis' => $kpis,
        ];
    }

    /**
     * Find a cash transfer by ID.
     */
    public function getTransferById(int $id): ?CashTransfer
    {
        return $this->transferRepo->findById($id);
    }

    /**
     * Create and record a cash transfer, synchronizing with Ledger & Site Cash.
     *
     * @param  array<string, mixed>  $data
     */
    public function createTransfer(array $data): CashTransfer
    {
        return DB::transaction(function () use ($data) {
            $formattedData = $this->prepareTransferData($data);
            $this->ensureSiteManagerOwnsSite((int) $formattedData['project_site_id']);

            $transfer = $this->transferRepo->create($formattedData);

            $this->syncLinkedAccountingRecords($transfer);

            return $transfer->fresh(['projectSite.client', 'creator', 'settler', 'ledger', 'siteCash']);
        });
    }

    /**
     * Update an existing cash transfer and re-sync linked records.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateTransfer(int $id, array $data): CashTransfer
    {
        return DB::transaction(function () use ($id, $data) {
            $transfer = $this->transferRepo->findById($id);
            if (! $transfer) {
                throw new \InvalidArgumentException("Cash transfer #{$id} not found.");
            }

            $formattedData = $this->prepareTransferData($data, $transfer);
            $this->ensureSiteManagerOwnsSite((int) $formattedData['project_site_id']);

            $updated = $this->transferRepo->update($transfer, $formattedData);

            $this->syncLinkedAccountingRecords($updated);

            return $updated->fresh(['projectSite.client', 'creator', 'settler', 'ledger', 'siteCash']);
        });
    }

    /**
     * Delete a cash transfer and clean up linked Ledger/Site Cash records.
     */
    public function deleteTransfer(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $transfer = $this->transferRepo->findById($id);
            if (! $transfer) {
                return false;
            }

            // Remove linked Ledger record if synced
            if ($transfer->ledger_id) {
                Ledger::where('id', $transfer->ledger_id)->delete();
            }

            // Remove linked AdminLedger record if synced
            if ($transfer->admin_ledger_id) {
                AdminLedger::where('id', $transfer->admin_ledger_id)->delete();
            }

            // Remove linked return Ledger record if settled
            if ($transfer->return_ledger_id) {
                Ledger::where('id', $transfer->return_ledger_id)->delete();
            }

            // Remove linked SiteCash record if synced
            if ($transfer->site_cash_id) {
                SiteCash::where('id', $transfer->site_cash_id)->delete();
            }

            // Remove linked return SiteCash record if settled
            if ($transfer->return_site_cash_id) {
                SiteCash::where('id', $transfer->return_site_cash_id)->delete();
            }

            // Remove receipt image if exists
            if (! empty($transfer->receipt_image)) {
                $this->deleteReceiptImage($transfer->receipt_image);
            }

            return $this->transferRepo->delete($transfer);
        });
    }

    /**
     * Mark a temporary transfer as settled / returned.
     *
     * @param  array<string, mixed>  $settlementData
     */
    public function markSettled(int $id, array $settlementData): CashTransfer
    {
        return DB::transaction(function () use ($id, $settlementData) {
            $transfer = $this->transferRepo->findById($id);
            if (! $transfer) {
                throw new \InvalidArgumentException("Cash transfer #{$id} not found.");
            }

            $transfer->update([
                'settlement_status' => 'settled',
                'settled_at' => now(),
                'settled_by' => Auth::id() ?? $transfer->created_by,
                'settlement_notes' => trim((string) ($settlementData['settlement_notes'] ?? '')),
            ]);

            // When a site_to_office temporary borrowing is returned/settled,
            // update notes in the linked ledger & site cash
            if ($transfer->ledger_id) {
                $ledger = Ledger::find($transfer->ledger_id);
                if ($ledger && ! str_contains($ledger->description ?? '', '[Settled / Returned]')) {
                    $ledger->update([
                        'description' => ($ledger->description ?? '').' [Settled / Returned]',
                    ]);
                }
            }

            if ($transfer->site_cash_id) {
                $siteCash = SiteCash::find($transfer->site_cash_id);
                if ($siteCash && ! str_contains($siteCash->description ?? '', '[Settled / Returned]')) {
                    $siteCash->update([
                        'description' => ($siteCash->description ?? '').' [Settled / Returned]',
                    ]);
                }
            }

            // If not already settled via Site Cash return form, create the settlement
            // accounting records so Site Cash Handling and Daily Ledger are updated
            if (! $transfer->return_site_cash_id && $transfer->project_site_id) {
                $site = $transfer->projectSite;
                $siteName = $site?->name ?? 'Site';
                $clientName = $site?->client?->name ?? '-';
                $siteCreatorId = $site?->created_by ?? ($transfer->created_by ?? Auth::id());
                $amUser = User::where('role', UserRole::AccountManager)->orWhere('role', 'account_manager')->first();
                $ledgerCreatorId = $transfer->accepted_by
                    ?: (Auth::check() && Auth::user()?->isAccountManager() ? Auth::id() : ($amUser?->id ?? (Auth::id() ?? $transfer->created_by)));
                $settler = Auth::user();
                $settlerName = $settler?->name ?? 'Accountant';
                $amount = (float) $transfer->amount;
                $purpose = $transfer->purpose ?: 'Temporary cash transfer';

                if ($transfer->transfer_direction === 'site_to_office') {
                    // Site lent money to Office. Now Accountant settles and refunds money back to Site!
                    // Site Cash receives Inflow (+)
                    $settleSiteCash = SiteCash::create([
                        'project_site_id' => $transfer->project_site_id,
                        'cash_transfer_id' => $transfer->id,
                        'transaction_type' => 'Advance Received',
                        'flow_type' => 'inflow',
                        'amount' => $amount,
                        'in_amount' => $amount,
                        'out_amount' => 0.00,
                        'is_expense' => false,
                        'payment_mode' => $transfer->payment_mode ?: 'Cash',
                        'payment_reference' => $transfer->payment_reference,
                        'bank_name' => $transfer->bank_name,
                        'paid_to' => $transfer->handed_over_by ?: 'Site Manager',
                        'paid_by' => $transfer->received_by ?: 'Account Manager',
                        'receiver_name' => $transfer->handed_over_by ?: 'Site Manager',
                        'bill_no' => 'SETTLE-CTR-'.$transfer->id,
                        'payment_date' => now()->toDateString(),
                        'payment_time' => now()->format('h:i A'),
                        'description' => "Refund of temporary cash borrowed by Accountant for Office use (#CTR-{$transfer->id}) - Purpose: {$purpose}. Settled by: {$settlerName}",
                        'wage_cash_source' => 'office_cash',
                        'created_by' => $siteCreatorId,
                    ]);

                    // Office Daily Ledger records Outflow (-)
                    $settleLedger = Ledger::create([
                        'entry_date' => now()->toDateString(),
                        'flow' => 'expense',
                        'source' => 'Construction',
                        'amount' => $amount,
                        'project_site_id' => $transfer->project_site_id,
                        'cash_transfer_id' => $transfer->id,
                        'site_name' => $siteName,
                        'client_name' => $clientName,
                        'transaction_type_name' => 'Cash Transfer Settlement (Repayment to Site)',
                        'description' => "Repayment of temporary cash borrowed from Site ({$siteName}) - Handed back to: {$transfer->handed_over_by}. Purpose: {$purpose}",
                        'wage_cash_source' => 'office_cash',
                        'created_by' => $ledgerCreatorId,
                    ]);

                    $transfer->updateQuietly([
                        'return_site_cash_id' => $settleSiteCash->id,
                        'return_ledger_id' => $settleLedger->id,
                    ]);
                } else {
                    // Office gave advance to Site. Now settled by Office
                    // Site Cash records Outflow (-)
                    $settleSiteCash = SiteCash::create([
                        'project_site_id' => $transfer->project_site_id,
                        'cash_transfer_id' => $transfer->id,
                        'transaction_type' => 'Balance Returned',
                        'flow_type' => 'outflow',
                        'amount' => $amount,
                        'in_amount' => 0.00,
                        'out_amount' => $amount,
                        'is_expense' => false,
                        'payment_mode' => $transfer->payment_mode ?: 'Cash',
                        'payment_reference' => $transfer->payment_reference,
                        'bank_name' => $transfer->bank_name,
                        'paid_to' => 'Account Manager',
                        'paid_by' => $transfer->received_by ?: 'Site Manager',
                        'receiver_name' => $transfer->handed_over_by ?: 'Account Manager',
                        'bill_no' => 'RET-CTR-'.$transfer->id,
                        'payment_date' => now()->toDateString(),
                        'payment_time' => now()->format('h:i A'),
                        'description' => "Balance returned to Account Manager for Cash Transfer #CTR-{$transfer->id} ({$purpose}) [Settled by Office]",
                        'wage_cash_source' => 'cash_transfer',
                        'created_by' => $siteCreatorId,
                    ]);

                    // Office Daily Ledger records Inflow (+)
                    $settleLedger = Ledger::create([
                        'entry_date' => now()->toDateString(),
                        'flow' => 'income',
                        'source' => 'Construction',
                        'amount' => $amount,
                        'project_site_id' => $transfer->project_site_id,
                        'cash_transfer_id' => $transfer->id,
                        'site_name' => $siteName,
                        'client_name' => $clientName,
                        'transaction_type_name' => 'Balance Returned (Cash Transfer)',
                        'description' => "Balance returned from Site for Cash Transfer #CTR-{$transfer->id} ({$purpose}) [Settled by Office]",
                        'wage_cash_source' => 'cash_transfer',
                        'created_by' => $ledgerCreatorId,
                    ]);

                    $transfer->updateQuietly([
                        'return_site_cash_id' => $settleSiteCash->id,
                        'return_ledger_id' => $settleLedger->id,
                    ]);
                }
            }

            return $transfer->fresh(['projectSite.client', 'creator', 'settler', 'ledger', 'siteCash']);
        });
    }

    /**
     * Accept a pending cash transfer by the recipient.
     *
     * @param  array<string, mixed>  $data
     */
    public function acceptTransfer(int $id, array $data = []): CashTransfer
    {
        return DB::transaction(function () use ($id, $data) {
            $transfer = $this->transferRepo->findById($id);
            if (! $transfer) {
                throw new \InvalidArgumentException("Cash transfer #{$id} not found.");
            }

            if ($transfer->isAccepted()) {
                throw new \InvalidArgumentException('This cash transfer has already been accepted.');
            }

            $user = Auth::user();
            if (! $transfer->canBeAcceptedBy($user)) {
                throw new AuthorizationException('You are not authorized to accept this cash transfer. Only the recipient or Admin can accept.');
            }

            $notes = ! empty($data['acceptance_notes'])
                ? trim((string) $data['acceptance_notes'])
                : (! empty($data['notes']) ? trim((string) $data['notes']) : null);

            $transfer->update([
                'acceptance_status' => 'accepted',
                'accepted_at' => now(),
                'accepted_by' => $user?->id,
                'acceptance_notes' => $notes,
            ]);

            $this->syncLinkedAccountingRecords($transfer);

            return $transfer->fresh(['projectSite.client', 'creator', 'accepter', 'settler', 'ledger', 'siteCash']);
        });
    }

    /**
     * Prevent Site Managers from writing transfers against another user's site.
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
     * Synchronize linked records in Daily Ledger and Site Cash.
     */
    protected function syncLinkedAccountingRecords(CashTransfer $transfer): void
    {
        $site = $transfer->projectSite;
        $siteName = $site?->name ?? 'Site';
        $clientName = $site?->client?->name ?? '-';
        $entryDateStr = $transfer->transfer_date instanceof \DateTimeInterface
            ? $transfer->transfer_date->format('Y-m-d')
            : (string) $transfer->transfer_date;
        $amount = (float) $transfer->amount;
        $purpose = $transfer->purpose ?: 'Inter-entity cash transfer';
        $acceptanceNote = $transfer->isAccepted() ? ' [Accepted]' : ' [Acceptance Pending]';

        if ($transfer->isSiteToOffice()) {
            // Case 1: Site to Office (Site Manager transfers / hands over cash to Account Manager / Office)
            // Daily Ledger: Office Inflow (+) - MUST ALWAYS BE INCOME IN ACCOUNT MANAGER'S LEDGER!
            if ($transfer->isAccepted()) {
                $statusNote = $transfer->settlement_status === 'settled' ? '[Settled / Returned]' : ($transfer->isProjectCash() ? '[Project Fund]' : '[Return Pending]');
                $amUser = User::where('role', UserRole::AccountManager)->orWhere('role', 'account_manager')->first();
                $ledgerCreatorId = $transfer->accepted_by
                    ?: (Auth::check() && Auth::user()?->isAccountManager() ? Auth::id() : ($amUser?->id ?? (Auth::id() ?? $transfer->created_by)));

                $txnTypeName = $transfer->isProjectCash() ? 'Project Cash Return from Site' : 'Cash Transfer from Site';

                $descPrefix = $transfer->is_temporary ? 'Temporary cash received from Site' : 'Cash received from Site';
                $ledgerData = [
                    'entry_date' => $entryDateStr,
                    'flow' => 'income',
                    'source' => 'Construction',
                    'amount' => $amount,
                    'project_site_id' => $transfer->project_site_id,
                    'cash_transfer_id' => $transfer->id,
                    'site_name' => $siteName,
                    'client_name' => $clientName,
                    'transaction_type_name' => $txnTypeName,
                    'description' => "{$descPrefix} ({$siteName}) for Office use - Given by: {$transfer->handed_over_by}, Received by: {$transfer->received_by}. Purpose: {$purpose} {$statusNote}",
                    'wage_cash_source' => 'office_cash',
                    'created_by' => $ledgerCreatorId,
                ];

                if ($transfer->ledger_id && ($existingLedger = Ledger::find($transfer->ledger_id))) {
                    $existingLedger->update($ledgerData);
                } else {
                    $ledger = Ledger::create($ledgerData);
                    $transfer->updateQuietly(['ledger_id' => $ledger->id]);
                }
            } elseif (! $transfer->isAccepted() && $transfer->ledger_id) {
                Ledger::find($transfer->ledger_id)?->delete();
                $transfer->updateQuietly(['ledger_id' => null]);
            }

            // Site Cash: Site Outflow (-) - Money given out by Site Manager
            if ($transfer->project_site_id) {
                $site = ProjectSite::find($transfer->project_site_id);
                $siteCashCreator = $site?->created_by ?? $transfer->created_by;

                $siteCashData = [
                    'project_site_id' => $transfer->project_site_id,
                    'cash_transfer_id' => $transfer->id,
                    'transaction_type' => 'Balance Returned',
                    'flow_type' => 'outflow',
                    'amount' => $amount,
                    'payment_mode' => $transfer->payment_mode ?: 'Cash',
                    'payment_reference' => $transfer->payment_reference,
                    'bank_name' => $transfer->bank_name,
                    'paid_to' => $transfer->received_by,
                    'paid_by' => $transfer->handed_over_by,
                    'receiver_name' => $transfer->received_by,
                    'is_expense' => false,
                    'in_amount' => 0.00,
                    'out_amount' => $amount,
                    'bill_no' => 'XFER-'.$transfer->id,
                    'payment_date' => $entryDateStr,
                    'payment_time' => $transfer->transfer_time ?: now()->format('h:i A'),
                    'description' => "Cash transferred to Office - Given to: {$transfer->received_by}. Purpose: {$purpose}",
                    'wage_cash_source' => $transfer->isProjectCash() ? 'project_cash' : 'office_cash',
                    'created_by' => $siteCashCreator,
                ];

                if ($transfer->site_cash_id && ($existingSc = SiteCash::find($transfer->site_cash_id))) {
                    $existingSc->update($siteCashData);
                } else {
                    $siteCash = SiteCash::create($siteCashData);
                    $transfer->updateQuietly(['site_cash_id' => $siteCash->id]);
                }
            }
        } elseif ($transfer->transfer_direction === 'office_to_site') {
            $creator = $transfer->created_by ? User::find($transfer->created_by) : null;
            $amUser = User::where('role', UserRole::AccountManager)->orWhere('role', 'account_manager')->first();

            if ($creator?->isAdmin()) {
                $recipientUser = User::where('name', $transfer->received_by)->first();
                $isRecipientAm = ($recipientUser && $recipientUser->isAccountManager())
                    || ($transfer->accepter && $transfer->accepter->isAccountManager())
                    || str_contains(strtolower((string) $transfer->received_by), 'account');

                if ($isRecipientAm) {
                    // Owner -> Account Manager: Once accepted, record as Income (+) in Account Manager's Daily Ledger
                    if ($transfer->isAccepted()) {
                        $ledgerCreatorId = $recipientUser?->id ?? $transfer->accepted_by ?? ($amUser?->id ?? $transfer->created_by);
                        $ledgerData = [
                            'entry_date' => $entryDateStr,
                            'flow' => 'income',
                            'source' => 'Construction',
                            'amount' => $amount,
                            'project_site_id' => $transfer->project_site_id,
                            'cash_transfer_id' => $transfer->id,
                            'site_name' => $siteName,
                            'client_name' => $clientName,
                            'transaction_type_name' => 'Cash Received from Owner',
                            'description' => "Cash received from Owner ({$siteName}) - Given by: {$transfer->handed_over_by}, Received by: {$transfer->received_by}. Purpose: {$purpose}{$acceptanceNote}",
                            'wage_cash_source' => 'office_cash',
                            'created_by' => $ledgerCreatorId,
                        ];

                        if ($transfer->ledger_id && ($existingLedger = Ledger::find($transfer->ledger_id))) {
                            $existingLedger->update($ledgerData);
                        } else {
                            $ledger = Ledger::create($ledgerData);
                            $transfer->updateQuietly(['ledger_id' => $ledger->id]);
                        }
                    } elseif ($transfer->ledger_id) {
                        Ledger::find($transfer->ledger_id)?->delete();
                        $transfer->updateQuietly(['ledger_id' => null]);
                    }

                    if ($transfer->site_cash_id) {
                        SiteCash::find($transfer->site_cash_id)?->delete();
                        $transfer->updateQuietly(['site_cash_id' => null]);
                    }
                } else {
                    // Owner -> Site Manager: Do not deduct from AM Daily Ledger; once accepted, record as Inflow (+) in Site Cash
                    if ($transfer->ledger_id) {
                        Ledger::find($transfer->ledger_id)?->delete();
                        $transfer->updateQuietly(['ledger_id' => null]);
                    }

                    if ($transfer->project_site_id && $transfer->isAccepted()) {
                        $site = ProjectSite::find($transfer->project_site_id);
                        $siteCashCreator = $site?->created_by ?? $transfer->created_by;

                        $siteCashData = [
                            'project_site_id' => $transfer->project_site_id,
                            'cash_transfer_id' => $transfer->id,
                            'transaction_type' => 'Advance Received',
                            'flow_type' => 'inflow',
                            'amount' => $amount,
                            'payment_mode' => $transfer->payment_mode ?: 'Cash',
                            'payment_reference' => $transfer->payment_reference,
                            'bank_name' => $transfer->bank_name,
                            'paid_to' => $transfer->received_by,
                            'paid_by' => $transfer->handed_over_by,
                            'receiver_name' => $transfer->received_by,
                            'is_expense' => false,
                            'in_amount' => $amount,
                            'out_amount' => 0.00,
                            'bill_no' => 'XFER-'.$transfer->id,
                            'payment_date' => $entryDateStr,
                            'payment_time' => $transfer->transfer_time ?: now()->format('h:i A'),
                            'description' => "Cash transfer received from Owner - Given by: {$transfer->handed_over_by}. Purpose: {$purpose}",
                            'wage_cash_source' => 'office_cash',
                            'created_by' => $siteCashCreator,
                        ];

                        if ($transfer->site_cash_id && ($existingSc = SiteCash::find($transfer->site_cash_id))) {
                            $existingSc->update($siteCashData);
                        } else {
                            $siteCash = SiteCash::create($siteCashData);
                            $transfer->updateQuietly(['site_cash_id' => $siteCash->id]);
                        }
                    } elseif (! $transfer->isAccepted() && $transfer->site_cash_id) {
                        SiteCash::find($transfer->site_cash_id)?->delete();
                        $transfer->updateQuietly(['site_cash_id' => null]);
                    }
                }
            } else {
                // Case 2: Office to Site (Account Manager -> Site Manager)
                // Daily Ledger: Office Outflow (-) - Money given out by Office
                $ledgerCreatorId = $creator?->isAccountManager()
                    ? $transfer->created_by
                    : ($transfer->accepted_by ?: ($amUser?->id ?? $transfer->created_by));

                $ledgerData = [
                    'entry_date' => $entryDateStr,
                    'flow' => 'expense',
                    'source' => 'Construction',
                    'amount' => $amount,
                    'project_site_id' => $transfer->project_site_id,
                    'cash_transfer_id' => $transfer->id,
                    'site_name' => $siteName,
                    'client_name' => $clientName,
                    'transaction_type_name' => 'Cash Transfer to Site',
                    'description' => "Cash transferred from Office to Site ({$siteName}) - Handed over by: {$transfer->handed_over_by}, Received by: {$transfer->received_by}. Purpose: {$purpose}{$acceptanceNote}",
                    'wage_cash_source' => 'office_cash',
                    'created_by' => $ledgerCreatorId,
                ];

                if ($transfer->ledger_id && ($existingLedger = Ledger::find($transfer->ledger_id))) {
                    $existingLedger->update($ledgerData);
                } else {
                    $ledger = Ledger::create($ledgerData);
                    $transfer->updateQuietly(['ledger_id' => $ledger->id]);
                }

                // Site Cash: Site Inflow (+) - Only active once Accepted by Site Manager!
                if ($transfer->project_site_id && $transfer->isAccepted()) {
                    $site = ProjectSite::find($transfer->project_site_id);
                    $siteCashCreator = $site?->created_by ?? $transfer->created_by;

                    $siteCashData = [
                        'project_site_id' => $transfer->project_site_id,
                        'cash_transfer_id' => $transfer->id,
                        'transaction_type' => 'Advance Received',
                        'flow_type' => 'inflow',
                        'amount' => $amount,
                        'payment_mode' => $transfer->payment_mode ?: 'Cash',
                        'payment_reference' => $transfer->payment_reference,
                        'bank_name' => $transfer->bank_name,
                        'paid_to' => $transfer->received_by,
                        'paid_by' => $transfer->handed_over_by,
                        'receiver_name' => $transfer->received_by,
                        'is_expense' => false,
                        'in_amount' => $amount,
                        'out_amount' => 0.00,
                        'bill_no' => 'XFER-'.$transfer->id,
                        'payment_date' => $entryDateStr,
                        'payment_time' => $transfer->transfer_time ?: now()->format('h:i A'),
                        'description' => "Cash transfer received from Office - Given by: {$transfer->handed_over_by}. Purpose: {$purpose}",
                        'wage_cash_source' => 'office_cash',
                        'created_by' => $siteCashCreator,
                    ];

                    if ($transfer->site_cash_id && ($existingSc = SiteCash::find($transfer->site_cash_id))) {
                        $existingSc->update($siteCashData);
                    } else {
                        $siteCash = SiteCash::create($siteCashData);
                        $transfer->updateQuietly(['site_cash_id' => $siteCash->id]);
                    }
                } elseif (! $transfer->isAccepted() && $transfer->site_cash_id) {
                    SiteCash::find($transfer->site_cash_id)?->delete();
                    $transfer->updateQuietly(['site_cash_id' => null]);
                }
            }
        } else {
            // Case 3: Project Cash (Account Manager transfers project funds received from Client/Owner to Site Manager)
            // Daily Ledger: Office / AM Project Outflow (-) - Deducts from project amount
            $amUser = User::where('role', UserRole::AccountManager)->orWhere('role', 'account_manager')->first();
            $ledgerCreatorId = User::find($transfer->created_by)?->isAccountManager()
                ? $transfer->created_by
                : ($transfer->accepted_by ?: ($amUser?->id ?? $transfer->created_by));

            $ledgerData = [
                'entry_date' => $entryDateStr,
                'flow' => 'expense',
                'source' => 'Construction',
                'amount' => $amount,
                'project_site_id' => $transfer->project_site_id,
                'cash_transfer_id' => $transfer->id,
                'site_name' => $siteName,
                'client_name' => $clientName,
                'transaction_type_name' => 'Project Cash Transfer to Site',
                'description' => "Project Cash transferred to Site Manager ({$siteName}) - Given by: {$transfer->handed_over_by}, Received by: {$transfer->received_by}. Purpose: {$purpose}{$acceptanceNote}",
                'wage_cash_source' => 'office_cash',
                'created_by' => $ledgerCreatorId,
            ];

            if ($transfer->ledger_id && ($existingLedger = Ledger::find($transfer->ledger_id))) {
                $existingLedger->update($ledgerData);
            } else {
                $ledger = Ledger::create($ledgerData);
                $transfer->updateQuietly(['ledger_id' => $ledger->id]);
            }

            // Site Cash: Site Inflow (+) - Added to Site Cash Handling ONLY when Accepted by Site Manager!
            if ($transfer->project_site_id && $transfer->isAccepted()) {
                $site = ProjectSite::find($transfer->project_site_id);
                $siteCashCreator = $site?->created_by ?? $transfer->created_by;

                $siteCashData = [
                    'project_site_id' => $transfer->project_site_id,
                    'cash_transfer_id' => $transfer->id,
                    'transaction_type' => 'Project Cash Received',
                    'flow_type' => 'inflow',
                    'amount' => $amount,
                    'payment_mode' => $transfer->payment_mode ?: 'Cash',
                    'payment_reference' => $transfer->payment_reference,
                    'bank_name' => $transfer->bank_name,
                    'paid_to' => $transfer->received_by,
                    'paid_by' => $transfer->handed_over_by,
                    'receiver_name' => $transfer->received_by,
                    'is_expense' => false,
                    'in_amount' => $amount,
                    'out_amount' => 0.00,
                    'bill_no' => 'XFER-'.$transfer->id,
                    'payment_date' => $entryDateStr,
                    'payment_time' => $transfer->transfer_time ?: now()->format('h:i A'),
                    'description' => "Project Cash received from Account Manager - Given by: {$transfer->handed_over_by}. Purpose: {$purpose}",
                    'wage_cash_source' => 'project_cash',
                    'created_by' => $siteCashCreator,
                ];

                if ($transfer->site_cash_id && ($existingSc = SiteCash::find($transfer->site_cash_id))) {
                    $existingSc->update($siteCashData);
                } else {
                    $siteCash = SiteCash::create($siteCashData);
                    $transfer->updateQuietly(['site_cash_id' => $siteCash->id]);
                }
            } elseif (! $transfer->isAccepted() && $transfer->site_cash_id) {
                SiteCash::find($transfer->site_cash_id)?->delete();
                $transfer->updateQuietly(['site_cash_id' => null]);
            }
        }

        $this->syncAdminLedgerDeduction($transfer, $siteName, $entryDateStr, $amount, $purpose);
    }

    /**
     * Deduct transferred amount in Admin Ledger when an Admin/Owner transfers cash out to a site.
     */
    protected function syncAdminLedgerDeduction(
        CashTransfer $transfer,
        string $siteName,
        string $entryDateStr,
        float $amount,
        string $purpose
    ): void {
        $creator = $transfer->created_by ? User::find($transfer->created_by) : Auth::user();
        $isOutgoingFromAdmin = $creator?->isAdmin()
            && in_array($transfer->transfer_direction, ['office_to_site', 'project_cash'], true);

        if ($isOutgoingFromAdmin) {
            $directionLabel = $transfer->transfer_direction === 'project_cash'
                ? 'Project Cash Transfer to Site'
                : 'Cash Transfer to Site';

            $adminLedgerData = [
                'entry_date' => $entryDateStr,
                'category' => 'cash_transfer',
                'flow' => 'expense',
                'project_site_id' => $transfer->project_site_id,
                'site_name' => $siteName,
                'is_site_based' => true,
                'amount' => $amount,
                'payment_mode' => $transfer->payment_mode ?: 'Cash',
                'payment_reference' => $transfer->payment_reference,
                'bank_name' => $transfer->bank_name,
                'description' => "{$directionLabel} ({$siteName}) #CTR-{$transfer->id} - Handed over by: {$transfer->handed_over_by}, Received by: {$transfer->received_by}. Purpose: {$purpose}",
                'created_by' => $transfer->created_by ?? (Auth::id() ?? 1),
            ];

            if ($transfer->admin_ledger_id && ($existingAdminLedger = AdminLedger::find($transfer->admin_ledger_id))) {
                $existingAdminLedger->update($adminLedgerData);
            } else {
                $adminLedger = AdminLedger::create($adminLedgerData);
                $transfer->updateQuietly(['admin_ledger_id' => $adminLedger->id]);
            }
        } elseif ($transfer->admin_ledger_id) {
            AdminLedger::where('id', $transfer->admin_ledger_id)->delete();
            $transfer->updateQuietly(['admin_ledger_id' => null]);
        }
    }

    /**
     * Format and sanitize data payload for CashTransfer.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepareTransferData(array $data, ?CashTransfer $existing = null): array
    {
        $direction = ! empty($data['transfer_direction']) ? (string) $data['transfer_direction'] : ($existing?->transfer_direction ?? 'office_to_site');
        $siteId = ! empty($data['project_site_id']) ? (int) $data['project_site_id'] : ($existing?->project_site_id ?? null);
        $amount = (float) ($data['amount'] ?? ($existing?->amount ?? 0));
        $date = ! empty($data['transfer_date']) ? Carbon::parse($data['transfer_date'])->toDateString() : ($existing?->transfer_date ?? now()->toDateString());
        $time = ! empty($data['transfer_time']) ? trim((string) $data['transfer_time']) : ($existing?->transfer_time ?? now()->format('h:i A'));
        $paymentMode = ! empty($data['payment_mode']) ? trim((string) $data['payment_mode']) : ($existing?->payment_mode ?? 'Cash');
        $paymentRef = ! empty($data['payment_reference']) ? trim((string) $data['payment_reference']) : ($existing?->payment_reference ?? null);
        $bankName = ! empty($data['bank_name']) ? trim((string) $data['bank_name']) : ($existing?->bank_name ?? null);
        $handedOverBy = ! empty($data['handed_over_by']) ? trim((string) $data['handed_over_by']) : ($existing?->handed_over_by ?? 'Accountant');
        $receivedBy = ! empty($data['received_by']) ? trim((string) $data['received_by']) : ($existing?->received_by ?? 'Site Engineer');

        // Automatically normalize direction when Site Manager transfers cash to Accountant
        $user = Auth::user();
        if ($user && $user->isSiteManager() && ! $existing) {
            if ($direction !== 'office_to_site') {
                $direction = 'site_to_office';
            }
            if ($handedOverBy === 'Accountant' || $handedOverBy === 'Accountant (Raja AM)' || empty($data['handed_over_by'])) {
                $handedOverBy = $user->name ?: 'Site Manager';
            }
            if ($receivedBy === 'Site Engineer' || $receivedBy === 'Site Manager' || empty($data['received_by'])) {
                $receivedBy = 'Accountant (Raja AM)';
            }
        }

        $handedLower = strtolower($handedOverBy);
        $receivedLower = strtolower($receivedBy);
        if (
            (str_contains($handedLower, 'site') || str_contains($handedLower, 'engineer') || str_contains($handedLower, 'murugan')) &&
            (str_contains($receivedLower, 'account') || str_contains($receivedLower, 'office') || str_contains($receivedLower, 'raja'))
        ) {
            $direction = 'site_to_office';
        }
        $purpose = ! empty($data['purpose']) ? trim((string) $data['purpose']) : ($existing?->purpose ?? null);
        $isTemporary = isset($data['is_temporary'])
            ? (bool) $data['is_temporary']
            : ($existing?->is_temporary ?? ($direction === 'site_to_office'));

        if ($direction === 'project_cash') {
            $isTemporary = false;
        }

        // Owner/Admin outgoing transfers are direct transfers (not temporary borrowings)
        if ($user && $user->isAdmin() && ! $existing && in_array($direction, ['office_to_site', 'project_cash'], true)) {
            $isTemporary = false;
        }

        $returnDate = ! empty($data['expected_return_date']) ? Carbon::parse($data['expected_return_date'])->toDateString() : ($existing?->expected_return_date ?? null);

        $settlementStatus = ! empty($data['settlement_status'])
            ? (string) $data['settlement_status']
            : ($existing?->settlement_status ?? ($isTemporary ? 'pending' : 'settled'));

        $acceptanceStatus = ! empty($data['acceptance_status'])
            ? (string) $data['acceptance_status']
            : ($existing?->acceptance_status ?? 'pending');

        $acceptedAt = ! empty($data['accepted_at'])
            ? Carbon::parse($data['accepted_at'])->toDateTimeString()
            : ($existing?->accepted_at ?? ($acceptanceStatus === 'accepted' ? now()->toDateTimeString() : null));

        $acceptedBy = ! empty($data['accepted_by'])
            ? (int) $data['accepted_by']
            : ($existing?->accepted_by ?? ($acceptanceStatus === 'accepted' ? (Auth::id() ?? 1) : null));

        $acceptanceNotes = isset($data['acceptance_notes'])
            ? trim((string) $data['acceptance_notes'])
            : ($existing?->acceptance_notes ?? null);

        $receiptImage = $existing?->receipt_image ?? null;
        if (! empty($data['receipt_image'])) {
            if ($data['receipt_image'] instanceof UploadedFile) {
                if ($existing?->receipt_image) {
                    $this->deleteReceiptImage($existing->receipt_image);
                }
                $receiptImage = $data['receipt_image']->store('cash_transfers', 'public');
            } elseif (is_string($data['receipt_image'])) {
                $receiptImage = $data['receipt_image'];
            }
        }

        return [
            'transfer_direction' => $direction,
            'project_site_id' => $siteId,
            'transfer_date' => $date,
            'transfer_time' => $time,
            'amount' => $amount,
            'payment_mode' => $paymentMode,
            'payment_reference' => $paymentRef,
            'bank_name' => $bankName,
            'handed_over_by' => $handedOverBy,
            'received_by' => $receivedBy,
            'purpose' => $purpose,
            'is_temporary' => $isTemporary,
            'expected_return_date' => $returnDate,
            'settlement_status' => $settlementStatus,
            'acceptance_status' => $acceptanceStatus,
            'accepted_at' => $acceptedAt,
            'accepted_by' => $acceptedBy,
            'acceptance_notes' => $acceptanceNotes,
            'receipt_image' => $receiptImage,
            'created_by' => ! empty($data['created_by']) ? (int) $data['created_by'] : (Auth::id() ?? ($existing?->created_by ?? 1)),
        ];
    }

    /**
     * Delete receipt image from storage.
     */
    protected function deleteReceiptImage(string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
