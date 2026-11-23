<?php

namespace App\Repositories\Eloquent;

use App\Models\CashTransfer;
use App\Repositories\Contracts\CashTransferRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class CashTransferRepository implements CashTransferRepositoryInterface
{
    /**
     * Restrict Site Managers to transfers for project sites they created.
     *
     * @param  Builder<CashTransfer>  $query
     * @return Builder<CashTransfer>
     */
    protected function scopeForAccessibleSites(Builder $query): Builder
    {
        $user = Auth::user();

        if ($user && $user->isSiteManager()) {
            $query->whereHas('projectSite', function ($siteQuery) use ($user) {
                $siteQuery->where('created_by', $user->id);
            });
        }

        return $query;
    }

    /**
     * Retrieve all cash transfers with relations and filters.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, CashTransfer>
     */
    public function getTransfers(array $filters = []): Collection
    {
        $query = $this->scopeForAccessibleSites(CashTransfer::with(['projectSite.client', 'creator', 'settler', 'ledger', 'siteCash']))
            ->orderByDesc('transfer_date')
            ->orderByDesc('id');

        if (! empty($filters['direction']) && $filters['direction'] !== 'all') {
            $query->where('transfer_direction', $filters['direction']);
        }

        if (! empty($filters['site_id']) && $filters['site_id'] !== 'all') {
            $query->where('project_site_id', (int) $filters['site_id']);
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('settlement_status', $filters['status']);
        }

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('transfer_date', [$filters['start_date'], $filters['end_date']]);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('handed_over_by', 'like', "%{$search}%")
                    ->orWhere('received_by', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%")
                    ->orWhere('payment_reference', 'like', "%{$search}%")
                    ->orWhereHas('projectSite', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->get();
    }

    /**
     * Find a cash transfer by ID.
     */
    public function findById(int $id): ?CashTransfer
    {
        return $this->scopeForAccessibleSites(CashTransfer::with(['projectSite.client', 'creator', 'settler', 'ledger', 'siteCash']))->find($id);
    }

    /**
     * Create a new cash transfer record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): CashTransfer
    {
        return CashTransfer::create($data);
    }

    /**
     * Update an existing cash transfer record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(CashTransfer $transfer, array $data): CashTransfer
    {
        $transfer->update($data);

        return $transfer->fresh(['projectSite.client', 'creator', 'settler', 'ledger', 'siteCash']);
    }

    /**
     * Delete a cash transfer record.
     */
    public function delete(CashTransfer $transfer): bool
    {
        return (bool) $transfer->delete();
    }
}
