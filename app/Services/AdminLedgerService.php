<?php

namespace App\Services;

use App\Models\AdminLedger;
use App\Models\Client;
use App\Models\ProjectSite;
use App\Models\Supplier;
use App\Repositories\Contracts\AdminLedgerRepositoryInterface;
use App\Services\Contracts\AdminLedgerServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdminLedgerService implements AdminLedgerServiceInterface
{
    public function __construct(
        public AdminLedgerRepositoryInterface $repository
    ) {}

    /**
     * Retrieve all entries matching filters.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, AdminLedger>
     */
    public function getAll(array $filters = []): Collection
    {
        return $this->repository->all($filters);
    }

    /**
     * Retrieve paginated entries.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<AdminLedger>
     */
    public function getPaginated(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    /**
     * Find entry by ID.
     */
    public function getById(int $id): ?AdminLedger
    {
        return $this->repository->findById($id);
    }

    /**
     * Create a new entry with file handling.
     *
     * @param  array<string, mixed>  $data
     */
    public function createEntry(array $data, ?UploadedFile $image = null): AdminLedger
    {
        $payload = $this->preparePayload($data);

        if ($image && $image->isValid()) {
            $payload['receipt_image'] = $image->store('admin_ledgers', 'public');
        }

        $payload['created_by'] = Auth::id() ?? 1;

        return $this->repository->create($payload);
    }

    /**
     * Update an entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateEntry(int $id, array $data, ?UploadedFile $image = null): bool
    {
        $payload = $this->preparePayload($data);

        if ($image && $image->isValid()) {
            $existing = $this->repository->findById($id);
            if ($existing?->receipt_image) {
                Storage::disk('public')->delete($existing->receipt_image);
            }
            $payload['receipt_image'] = $image->store('admin_ledgers', 'public');
        }

        return $this->repository->update($id, $payload);
    }

    /**
     * Delete an entry.
     */
    public function deleteEntry(int $id): bool
    {
        $existing = $this->repository->findById($id);
        if ($existing?->receipt_image) {
            Storage::disk('public')->delete($existing->receipt_image);
        }

        return $this->repository->delete($id);
    }

    /**
     * Get summary metrics.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, float>
     */
    public function getSummary(array $filters = []): array
    {
        return $this->repository->getSummary($filters);
    }

    /**
     * Calculate running balances for a collection (ordered chronologically ascending).
     *
     * @param  Collection<int, AdminLedger>  $entries
     * @return array<int, float>
     */
    public function calculateRunningBalances(Collection $entries): array
    {
        $sorted = $entries->sortBy([
            ['entry_date', 'asc'],
            ['id', 'asc'],
        ]);

        $running = 0.0;
        $balances = [];

        foreach ($sorted as $entry) {
            $amt = (float) $entry->amount;
            if ($entry->flow === 'income') {
                $running += $amt;
            } else {
                $running -= $amt;
            }
            $balances[$entry->id] = $running;
        }

        return $balances;
    }

    /**
     * Prepare payload with party and site names resolved.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function preparePayload(array $data): array
    {
        $category = $data['category'] ?? 'supplier_payment';
        $flow = ($category === 'client_payment') ? 'income' : 'expense';

        $isSiteBased = true;
        if (isset($data['is_site_based'])) {
            $isSiteBased = filter_var($data['is_site_based'], FILTER_VALIDATE_BOOLEAN);
        } elseif (isset($data['supplier_payment_scope'])) {
            $isSiteBased = ($data['supplier_payment_scope'] === 'site_based');
        }

        $payload = [
            'entry_date' => $data['entry_date'] ?? now()->toDateString(),
            'category' => $category,
            'flow' => $flow,
            'amount' => (float) ($data['amount'] ?? 0),
            'payment_mode' => $data['payment_mode'] ?? 'Cash',
            'payment_reference' => $data['payment_reference'] ?? $data['bill_no'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'cheque_no' => $data['cheque_no'] ?? null,
            'description' => $data['description'] ?? null,
            'is_site_based' => $isSiteBased,
        ];

        if ($category === 'client_payment') {
            $payload['client_id'] = ! empty($data['client_id']) ? (int) $data['client_id'] : null;
            if ($payload['client_id']) {
                $client = Client::find($payload['client_id']);
                $payload['client_name'] = $client?->name ?? ($data['client_name'] ?? null);
            } else {
                $payload['client_name'] = $data['client_name'] ?? null;
            }

            $payload['project_site_id'] = ! empty($data['project_site_id']) ? (int) $data['project_site_id'] : null;
            if ($payload['project_site_id']) {
                $site = ProjectSite::find($payload['project_site_id']);
                $payload['site_name'] = $site?->name ?? ($data['site_name'] ?? null);
            } else {
                $payload['site_name'] = $data['site_name'] ?? null;
            }

            $payload['supplier_id'] = null;
            $payload['supplier_name'] = null;
            $payload['purchase_order_no'] = null;
        } else {
            // Supplier Payment
            $payload['supplier_id'] = ! empty($data['supplier_id']) ? (int) $data['supplier_id'] : null;
            if ($payload['supplier_id']) {
                $supplier = Supplier::find($payload['supplier_id']);
                $payload['supplier_name'] = $supplier?->name ?? ($data['supplier_name'] ?? null);
            } else {
                $payload['supplier_name'] = $data['supplier_name'] ?? null;
            }

            if ($isSiteBased && ! empty($data['project_site_id'])) {
                $payload['project_site_id'] = (int) $data['project_site_id'];
                $site = ProjectSite::find($payload['project_site_id']);
                $payload['site_name'] = $site?->name ?? ($data['site_name'] ?? null);
                $payload['purchase_order_no'] = $data['purchase_order_no'] ?? null;
            } else {
                $payload['project_site_id'] = null;
                $payload['site_name'] = 'All Sites (Total)';
                $payload['purchase_order_no'] = null;
            }

            $payload['client_id'] = null;
            $payload['client_name'] = null;
        }

        return $payload;
    }
}
