<?php

namespace App\Http\Controllers;

use App\Models\DailyAllocation;
use App\Models\ProjectSite;
use App\Models\SiteCash;
use App\Models\TemporaryBill;
use App\Models\User;
use App\Services\IdCipherService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TemporaryBillController extends Controller
{
    /**
     * Display the saved Temporary Bills list table.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $billsQuery = TemporaryBill::with(['projectSite.client', 'creator'])
            ->orderByDesc('bill_date')
            ->orderByDesc('id');

        if ($user && ! $user->isAdmin()) {
            $billsQuery->whereHas('projectSite', function ($sq) use ($user) {
                $sq->where('created_by', $user->id);
            });
        }

        $bills = $billsQuery->get();
        $sites = $this->getAccessibleSites($user);

        return view('temporary-billing.index', compact('bills', 'sites'));
    }

    /**
     * Show the Temporary Billing preparation form.
     */
    public function create(Request $request): View
    {
        $sites = $this->getAccessibleSites($request->user());
        $selectedSiteId = $request->query('project_site_id');
        if ($selectedSiteId && ! $sites->contains('id', (int) $selectedSiteId)) {
            $selectedSiteId = null;
        }
        $bill = null;

        return view('temporary-billing.create', compact('sites', 'selectedSiteId', 'bill'));
    }

    /**
     * Show the edit form for an existing Temporary Bill.
     */
    public function edit(Request $request, string $encryptedId): View|RedirectResponse
    {
        $id = is_numeric($encryptedId) ? (int) $encryptedId : IdCipherService::decrypt($encryptedId);
        $bill = TemporaryBill::with(['projectSite.client'])->find($id);

        if (! $bill || ! $this->canAccessSite($request->user(), $bill->projectSite)) {
            return redirect()->route('temporary-billing.index')->with('error', 'Temporary bill not found.');
        }

        $sites = $this->getAccessibleSites($request->user());
        $selectedSiteId = $bill->project_site_id;

        return view('temporary-billing.create', compact('sites', 'selectedSiteId', 'bill'));
    }

    /**
     * Fetch date-wise Painter & Electrician payments for a selected Project Site.
     */
    public function getSitePayments(Request $request): JsonResponse
    {
        $request->validate([
            'project_site_id' => 'required|integer|exists:project_sites,id',
            'trade_filter' => 'nullable|string|in:all,painter,electrician',
        ]);

        $siteId = (int) $request->query('project_site_id');
        $site = ProjectSite::find($siteId);

        if (! $site || ! $this->canAccessSite($request->user(), $site)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to project site.',
                'items' => [],
                'total_amount' => 0,
            ], 403);
        }

        $tradeFilter = $request->query('trade_filter', 'all');

        $items = collect();

        // 1. Gather Painter & Electrician entries from Daily Allocations
        $allocations = DailyAllocation::with('items')
            ->where('project_site_id', $siteId)
            ->orderBy('date', 'asc')
            ->get();

        $allocatedDateTrades = [];

        foreach ($allocations as $alloc) {
            $dateStr = $alloc->date ? Carbon::parse($alloc->date)->format('Y-m-d') : '';

            foreach ($alloc->items as $item) {
                $tradeType = $this->detectPainterOrElectrician(
                    (string) $item->category_code,
                    (string) $item->name_en,
                    (string) $item->name_ta
                );

                if (! $tradeType) {
                    continue;
                }

                if ($tradeFilter !== 'all' && $tradeType !== $tradeFilter) {
                    continue;
                }

                $subtotal = (float) $item->subtotal;
                $workerCount = (float) $item->worker_count;
                $dailyWage = (float) $item->daily_wage;
                $tradeLabel = $tradeType === 'painter' ? 'Painter' : 'Electrician';
                $nameLabel = $item->name_ta ?: ($item->name_en ?: $tradeLabel);
                $paidBy = $item->paid_by ?: 'Unpaid';
                $isPaid = ! empty($item->paid_by) || ! empty($item->ledger_id);

                $allocatedDateTrades[$dateStr.'_'.$tradeType] = true;

                $items->push([
                    'id' => 'da_'.$item->id,
                    'date' => $dateStr,
                    'trade' => $tradeLabel,
                    'trade_key' => $tradeType,
                    'description' => $nameLabel.' ('.(int) $workerCount.' workers)',
                    'worker_count' => (int) $workerCount,
                    'daily_wage' => round($dailyWage, 2),
                    'paid_by' => $paidBy,
                    'is_paid' => $isPaid,
                    'source' => 'Daily Allocation',
                    'original_amount' => round($subtotal, 2),
                    'billed_amount' => round($subtotal, 2),
                ]);
            }
        }

        // 2. Include standalone SiteCash Painter/Electrician wage payments not already covered by Daily Allocation on that date
        $siteCashPayments = SiteCash::query()
            ->where('project_site_id', $siteId)
            ->where('flow_type', 'outflow')
            ->where('transaction_type', 'Daily Wage Payment')
            ->where(function ($q) {
                $q->where('paid_to', 'like', '%Painter%')
                    ->orWhere('paid_to', 'like', '%Electrician%')
                    ->orWhere('description', 'like', '%Painter%')
                    ->orWhere('description', 'like', '%Electrician%');
            })
            ->orderBy('payment_date', 'asc')
            ->get();

        foreach ($siteCashPayments as $sc) {
            $dateStr = $sc->payment_date ? Carbon::parse($sc->payment_date)->format('Y-m-d') : '';
            $text = strtolower(trim(($sc->paid_to ?? '').' '.($sc->description ?? '')));
            $tradeType = str_contains($text, 'painter') ? 'painter' : (str_contains($text, 'electrician') ? 'electrician' : null);

            if (! $tradeType) {
                continue;
            }

            if ($tradeFilter !== 'all' && $tradeType !== $tradeFilter) {
                continue;
            }

            // Skip if this date+trade is already represented from DailyAllocation to prevent duplicate rows
            if (isset($allocatedDateTrades[$dateStr.'_'.$tradeType])) {
                continue;
            }

            $amt = (float) $sc->amount;
            $tradeLabel = $tradeType === 'painter' ? 'Painter' : 'Electrician';

            $items->push([
                'id' => 'sc_'.$sc->id,
                'date' => $dateStr,
                'trade' => $tradeLabel,
                'trade_key' => $tradeType,
                'description' => $sc->description ?: ($tradeLabel.' Payment'),
                'worker_count' => 1,
                'daily_wage' => round($amt, 2),
                'paid_by' => $sc->paid_by ?: ($sc->paid_to ?: 'Site Cash'),
                'is_paid' => true,
                'source' => 'Site Cash',
                'original_amount' => round($amt, 2),
                'billed_amount' => round($amt, 2),
            ]);
        }

        $sorted = $items->sortBy('date')->values();
        $totalAmount = (float) $sorted->sum('billed_amount');

        return response()->json([
            'success' => true,
            'items' => $sorted,
            'total_amount' => round($totalAmount, 2),
        ]);
    }

    /**
     * Store a newly created Temporary Bill and return its print URL.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateBillRequest($request);
        $site = ProjectSite::find((int) $validated['project_site_id']);

        if (! $site || ! $this->canAccessSite($request->user(), $site)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to project site.',
            ], 403);
        }

        $year = Carbon::parse($validated['bill_date'])->format('Y');
        $prefix = "TB-{$year}-";
        $latest = TemporaryBill::where('bill_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->first();

        $nextSeq = 1;
        if ($latest && preg_match('/-(\d+)$/', $latest->bill_number, $m)) {
            $nextSeq = ((int) $m[1]) + 1;
        }
        $billNumber = $prefix.str_pad((string) $nextSeq, 3, '0', STR_PAD_LEFT);

        $totalAmount = collect($validated['items'])->sum(fn (array $row) => (float) $row['billed_amount']);

        $bill = TemporaryBill::create([
            'project_site_id' => (int) $validated['project_site_id'],
            'bill_number' => $billNumber,
            'bill_date' => $validated['bill_date'],
            'title' => $validated['title'] ?? 'Painter & Electrician Temporary Bill',
            'trade_filter' => $validated['trade_filter'] ?? 'all',
            'items' => array_values($validated['items']),
            'total_amount' => round($totalAmount, 2),
            'notes' => $validated['notes'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Temporary Bill {$billNumber} saved successfully.",
            'bill' => $bill->load(['projectSite.client', 'creator']),
            'print_url' => route('temporary-billing.print', $bill->encrypted_id),
        ], 201);
    }

    /**
     * Update an existing Temporary Bill.
     */
    public function update(Request $request, string $encryptedId): JsonResponse
    {
        $id = is_numeric($encryptedId) ? (int) $encryptedId : IdCipherService::decrypt($encryptedId);
        $bill = TemporaryBill::with('projectSite')->find($id);

        if (! $bill || ! $this->canAccessSite($request->user(), $bill->projectSite)) {
            return response()->json([
                'success' => false,
                'message' => 'Temporary bill not found.',
            ], 404);
        }

        $validated = $this->validateBillRequest($request);
        $newSite = ProjectSite::find((int) $validated['project_site_id']);
        if (! $newSite || ! $this->canAccessSite($request->user(), $newSite)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to project site.',
            ], 403);
        }
        $totalAmount = collect($validated['items'])->sum(fn (array $row) => (float) $row['billed_amount']);

        $bill->update([
            'project_site_id' => (int) $validated['project_site_id'],
            'bill_date' => $validated['bill_date'],
            'title' => $validated['title'] ?? 'Painter & Electrician Temporary Bill',
            'trade_filter' => $validated['trade_filter'] ?? 'all',
            'items' => array_values($validated['items']),
            'total_amount' => round($totalAmount, 2),
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Temporary Bill {$bill->bill_number} updated successfully.",
            'bill' => $bill->fresh(['projectSite.client', 'creator']),
            'print_url' => route('temporary-billing.print', $bill->encrypted_id),
        ]);
    }

    /**
     * Render the A4 Print / Reprint view for a saved Temporary Bill.
     */
    public function print(Request $request, string $encryptedId): View|RedirectResponse
    {
        $id = is_numeric($encryptedId) ? (int) $encryptedId : IdCipherService::decrypt($encryptedId);
        $bill = TemporaryBill::with(['projectSite.client', 'creator'])->find($id);

        if (! $bill || ! $this->canAccessSite($request->user(), $bill->projectSite)) {
            return redirect()->route('temporary-billing.index')->with('error', 'Temporary bill not found.');
        }

        return view('temporary-billing.print', [
            'bill' => $bill,
            'site' => $bill->projectSite,
        ]);
    }

    /**
     * Delete a saved Temporary Bill.
     */
    public function destroy(Request $request, string $encryptedId): JsonResponse
    {
        $id = is_numeric($encryptedId) ? (int) $encryptedId : IdCipherService::decrypt($encryptedId);
        $bill = TemporaryBill::with('projectSite')->find($id);

        if (! $bill || ! $this->canAccessSite($request->user(), $bill->projectSite)) {
            return response()->json([
                'success' => false,
                'message' => 'Temporary bill not found.',
            ], 404);
        }

        $bill->delete();

        return response()->json([
            'success' => true,
            'message' => 'Temporary bill deleted successfully.',
        ]);
    }

    /**
     * Get project sites accessible to the given user.
     *
     * @return Collection<int, ProjectSite>
     */
    protected function getAccessibleSites(?User $user): Collection
    {
        $query = ProjectSite::with('client')->orderBy('name');

        if ($user && ! $user->isAdmin()) {
            $query->where('created_by', $user->id);
        }

        return $query->get();
    }

    /**
     * Determine whether the user can access the given project site.
     */
    protected function canAccessSite(?User $user, ?ProjectSite $site): bool
    {
        if (! $site) {
            return false;
        }

        if (! $user || $user->isAdmin()) {
            return true;
        }

        return (int) $site->created_by === (int) $user->id;
    }

    /**
     * Validate Temporary Bill store/update request payload.
     *
     * @return array<string, mixed>
     */
    protected function validateBillRequest(Request $request): array
    {
        return $request->validate([
            'project_site_id' => 'required|integer|exists:project_sites,id',
            'bill_date' => 'required|date',
            'title' => 'nullable|string|max:255',
            'trade_filter' => 'nullable|string|in:all,painter,electrician',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.date' => 'required|date',
            'items.*.trade' => 'required|string|max:100',
            'items.*.description' => 'required|string|max:500',
            'items.*.worker_count' => 'nullable|numeric|min:0',
            'items.*.daily_wage' => 'nullable|numeric|min:0',
            'items.*.paid_by' => 'nullable|string|max:100',
            'items.*.original_amount' => 'nullable|numeric|min:0',
            'items.*.billed_amount' => 'required|numeric|min:0',
        ]);
    }

    /**
     * Identify whether a worker category represents Painter or Electrician (excluding Wireman).
     */
    protected function detectPainterOrElectrician(string $catCode, string $nameEn, string $nameTa): ?string
    {
        $code = strtoupper(trim($catCode));
        $en = strtolower(trim($nameEn));
        $ta = trim($nameTa);

        if ($code === 'CAT-009' || str_contains($en, 'wireman') || str_contains($ta, 'மின்கம்பி') || str_contains($ta, 'மின் கம்பி')) {
            return null;
        }

        if ($code === 'CAT-007' || str_contains($en, 'painter') || str_contains($ta, 'பெயிண்டர்')) {
            return 'painter';
        }

        if ($code === 'CAT-008' || str_contains($en, 'electrician') || str_contains($ta, 'எலக்ட்ரீஷியன்')) {
            return 'electrician';
        }

        return null;
    }
}
