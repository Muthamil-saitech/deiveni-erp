<?php

namespace App\Http\Controllers;

use App\Models\Ledger;
use App\Models\Material;
use App\Models\SiteCash;
use App\Models\Unit;
use App\Models\WorkerCategory;
use App\Services\Contracts\DailyAllocationServiceInterface;
use App\Services\Contracts\MaterialServiceInterface;
use App\Services\Contracts\ProjectSiteServiceInterface;
use App\Services\Contracts\WorkerCategoryServiceInterface;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DailyAllocationController extends Controller
{
    public function __construct(
        public DailyAllocationServiceInterface $dailyAllocationService,
        public ProjectSiteServiceInterface $projectSiteService,
        public WorkerCategoryServiceInterface $workerCategoryService,
        public MaterialServiceInterface $materialService
    ) {}

    /**
     * Display the Daily Allocation page with dynamic project sites and worker categories.
     */
    public function index(Request $request): View
    {
        $sites = $this->projectSiteService->getAllSites();
        $workerCategories = $this->workerCategoryService->getAllCategories()
            ->where('status', 'Active')
            ->values();
        $materials = $this->materialService->getAllMaterials()
            ->where('status', 'Active')
            ->where('type', Material::TYPE_OTHER)
            ->values();

        return view('site-management.daily-allocation', compact('sites', 'workerCategories', 'materials'));
    }

    /**
     * Get allocation data for a selected project site and date.
     */
    public function getByDate(Request $request): JsonResponse
    {
        $request->validate([
            'project_site_id' => 'required|integer',
            'date' => 'required|date_format:Y-m-d',
        ]);

        $siteId = (int) $request->input('project_site_id');
        $date = $request->input('date');

        $allocation = $this->dailyAllocationService->getAllocation($siteId, $date);

        // Calculate week bounds to return saved days status for current week strip
        $dt = Carbon::parse($date);
        $weekStart = $dt->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = $dt->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $weekSaved = $this->dailyAllocationService->getWeekSavedStatus($siteId, $weekStart, $weekEnd);

        // Fetch actual financial payments made by Account Manager ONLY for Painter & Electrician on this site & date
        $amWageSiteCash = SiteCash::query()
            ->where('project_site_id', $siteId)
            ->whereDate('payment_date', $date)
            ->where('paid_by', 'Account Manager')
            ->where('flow_type', 'outflow')
            ->where(function ($q) {
                $q->where('paid_to', 'like', '%Painter%')
                    ->orWhere('paid_to', 'like', '%Electrician%')
                    ->orWhere('description', 'like', '%Painter%')
                    ->orWhere('description', 'like', '%Electrician%');
            })
            ->where('transaction_type', '!=', 'Balance Returned')
            ->where('description', 'not like', '%Balance returned%')
            ->get();

        $amWageLedgers = Ledger::query()
            ->where('project_site_id', $siteId)
            ->whereDate('entry_date', $date)
            ->where(function ($q) {
                $q->where('description', 'like', '%Painter & Electrician daily wages%')
                    ->orWhere('description', 'like', '%Daily wages for Painter & Electrician%');
            })
            ->where('description', 'not like', '%Balance returned%')
            ->get();

        $actualPaidAmount = 0.0;
        $amPaidDetails = [];

        foreach ($amWageSiteCash as $sc) {
            $amt = (float) $sc->amount;
            if ($amt > 0) {
                $actualPaidAmount += $amt;
                $amPaidDetails[] = [
                    'source' => 'SiteCash',
                    'id' => $sc->id,
                    'amount' => $amt,
                    'paid_to' => $sc->paid_to,
                    'paid_by' => $sc->paid_by,
                    'cash_source' => $sc->wage_cash_source,
                    'description' => $sc->description,
                ];
            }
        }

        if ($actualPaidAmount === 0.0) {
            foreach ($amWageLedgers as $l) {
                $amt = (float) $l->amount;
                if ($amt > 0) {
                    $actualPaidAmount += $amt;
                    $amPaidDetails[] = [
                        'source' => 'Ledger',
                        'id' => $l->id,
                        'amount' => $amt,
                        'paid_to' => 'Painter / Electrician',
                        'paid_by' => $l->paid_by ?: 'Account Manager',
                        'cash_source' => 'Office Cash',
                        'description' => $l->description,
                    ];
                }
            }
        }

        // Check allocation items marked paid_by === 'Account Manager' strictly for Painter (CAT-007) and Electrician (CAT-008) ONLY
        $allocatedAmPaid = 0.0;
        $amTradesBreakdown = [];
        if ($allocation && $allocation->items) {
            foreach ($allocation->items as $item) {
                $catCode = strtoupper(trim((string) $item->category_code));
                $nameEn = strtolower(trim((string) $item->name_en));
                $nameTa = trim((string) $item->name_ta);

                // Strictly exclude Wireman
                if ($catCode === 'CAT-009' || str_contains($nameEn, 'wireman') || str_contains($nameTa, 'மின் கம்பி') || str_contains($nameTa, 'மின்கம்பி')) {
                    continue;
                }

                $isMatch = in_array($catCode, ['CAT-007', 'CAT-008'], true)
                    || str_contains($nameEn, 'painter')
                    || str_contains($nameEn, 'electrician')
                    || str_contains($nameTa, 'பெயிண்டர்')
                    || str_contains($nameTa, 'எலக்ட்ரீஷியன்');

                if ($item->paid_by === 'Account Manager' && $isMatch && (float) $item->subtotal > 0) {
                    $sub = (float) $item->subtotal;
                    $allocatedAmPaid += $sub;
                    $amTradesBreakdown[] = "{$item->worker_count} {$item->name_ta} (₹{$sub})";
                }
            }
        }

        $finalPaidAmAmount = $allocatedAmPaid > 0 ? $allocatedAmPaid : $actualPaidAmount;

        return response()->json([
            'success' => true,
            'allocation' => $allocation,
            'week_saved' => $weekSaved,
            'am_wage_payment' => [
                'has_payment' => $finalPaidAmAmount > 0,
                'paid_amount' => $finalPaidAmAmount,
                'details' => $amPaidDetails,
                'breakdown' => $amTradesBreakdown,
            ],
        ]);
    }

    /**
     * Get day-by-day historical log records for the selected site and date range.
     */
    public function getHistory(Request $request): JsonResponse
    {
        $request->validate([
            'project_site_id' => 'required|integer',
            'from_date' => 'nullable|date_format:Y-m-d',
            'to_date' => 'nullable|date_format:Y-m-d',
        ]);

        $siteId = (int) $request->input('project_site_id');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $history = $this->dailyAllocationService->getAllocationHistory($siteId, $fromDate, $toDate);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Store or update daily allocation.
     * Automatically syncs into weekly wages.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_site_id' => 'required|exists:project_sites,id',
            'date' => 'required|date_format:Y-m-d',
            'total_labour_count' => 'required|integer|min:0',
            'total_labour_wage' => 'required|numeric|min:0',
            'total_expenses' => 'nullable|numeric|min:0',
            'grand_total' => 'required|numeric|min:0',
            'trade_summary' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.category_code' => 'nullable|string|max:50',
            'items.*.worker_category_id' => 'nullable|integer',
            'items.*.name_ta' => 'required_with:items|string|max:150',
            'items.*.name_en' => 'nullable|string|max:150',
            'items.*.daily_wage' => 'nullable|numeric|min:0',
            'items.*.master_wage' => 'nullable|numeric|min:0',
            'items.*.worker_count' => 'nullable|integer|min:0',
            'items.*.subtotal' => 'nullable|numeric|min:0',
            'items.*.paid_by' => 'nullable|string|max:100',
            'items.*.wage_cash_source' => 'nullable|string|max:100',
            'items.*.ledger_id' => 'nullable|integer',
            'items.*.paid_at' => 'nullable|date',
            'expenses' => 'nullable|array',
            'expenses.*.item_name' => 'nullable|string|max:255',
            'expenses.*.quantity' => 'nullable|numeric|min:0',
            'expenses.*.unit_price' => 'nullable|numeric|min:0',
            'expenses.*.amount' => 'nullable|numeric|min:0',
        ]);

        $validated['created_by'] = auth()->id();

        // Task 1: Auto-create any new/custom worker category in Master if not existing
        if (! empty($validated['items']) && is_array($validated['items'])) {
            foreach ($validated['items'] as $idx => $item) {
                if (empty($item['worker_category_id'])) {
                    $nameTa = trim((string) ($item['name_ta'] ?? ''));
                    $nameEn = ! empty($item['name_en']) ? trim((string) $item['name_en']) : $nameTa;

                    if ($nameTa !== '') {
                        $existingCat = WorkerCategory::where('name_ta', $nameTa)
                            ->orWhere('name_en', $nameEn)
                            ->first();

                        if (! $existingCat) {
                            $enTaken = WorkerCategory::where('name_en', $nameEn)->exists();
                            if ($enTaken && $nameEn === $nameTa) {
                                $nameEn = $nameEn.' (Custom)';
                            }
                            $taTaken = WorkerCategory::where('name_ta', $nameTa)->exists();
                            if ($taTaken && $nameEn === $nameTa) {
                                $nameTa = $nameTa.' (புதியது)';
                            }

                            $dailyWage = (float) ($item['daily_wage'] ?? $item['master_wage'] ?? 0);
                            $existingCat = $this->workerCategoryService->createCategory([
                                'name_ta' => $nameTa,
                                'name_en' => $nameEn,
                                'daily_wage' => max(0, $dailyWage),
                                'shift' => 'Day',
                                'status' => 'Active',
                            ]);
                        }

                        $validated['items'][$idx]['worker_category_id'] = $existingCat->id;
                        $validated['items'][$idx]['category_code'] = $existingCat->code;
                        $validated['items'][$idx]['master_wage'] = $existingCat->daily_wage;
                    }
                }
            }
        }

        // Auto-create any new expense item in Materials Master if not existing
        if (! empty($validated['expenses']) && is_array($validated['expenses'])) {
            foreach ($validated['expenses'] as $exp) {
                $rawTitle = trim((string) ($exp['item_name'] ?? ''));
                if ($rawTitle === '') {
                    continue;
                }

                $this->findOrCreateMaterial($rawTitle);
            }
        }

        $allocation = $this->dailyAllocationService->saveAllocation($validated);

        $refreshedCategories = $this->workerCategoryService->getAllCategories()
            ->where('status', 'Active')
            ->values();

        $refreshedMaterials = $this->materialService->getAllMaterials()
            ->where('status', 'Active')
            ->where('type', Material::TYPE_OTHER)
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Daily allocation saved successfully! Weekly wages record synced automatically.',
            'data' => $allocation,
            'worker_categories' => $refreshedCategories,
            'materials' => $refreshedMaterials,
        ]);
    }

    /**
     * Find existing material or create a new one in Materials Master.
     */
    public function findOrCreateMaterial(string $rawTitle, ?int $unitId = null, ?string $customNameTa = null): Material
    {
        $rawTitle = trim($rawTitle);
        $nameEn = $rawTitle;
        $nameTa = $customNameTa ? trim($customNameTa) : $rawTitle;

        if (! $customNameTa) {
            if (str_contains($rawTitle, ' - ')) {
                $parts = explode(' - ', $rawTitle, 2);
                $p0 = trim($parts[0]);
                $p1 = trim($parts[1]);
                if ($p0 !== '' && $p1 !== '') {
                    $nameEn = $p0;
                    $nameTa = $p1;
                }
            } elseif (preg_match('/^(.*?)\s*\((.*?)\)$/u', $rawTitle, $matches)) {
                $p0 = trim($matches[1]);
                $p1 = trim($matches[2]);
                if ($p0 !== '' && $p1 !== '') {
                    $nameEn = $p0;
                    $nameTa = $p1;
                }
            }
        }

        // Check if existing material matches
        $existing = Material::query()
            ->with('unit')
            ->where(function ($q) use ($rawTitle, $nameEn, $nameTa) {
                $q->whereRaw('LOWER(name_en) = ?', [strtolower($rawTitle)])
                    ->orWhereRaw('LOWER(name_ta) = ?', [strtolower($rawTitle)])
                    ->orWhereRaw('LOWER(name_en) = ?', [strtolower($nameEn)])
                    ->orWhereRaw('LOWER(name_ta) = ?', [strtolower($nameTa)]);
            })
            ->first();

        if (! $existing) {
            $lowerRaw = strtolower($rawTitle);
            $existing = Material::all()->first(function ($m) use ($lowerRaw) {
                $combinedDash = strtolower(trim("{$m->name_en} - {$m->name_ta}"));
                $combinedParen = strtolower(trim("{$m->name_en} ({$m->name_ta})"));

                return $combinedDash === $lowerRaw || $combinedParen === $lowerRaw;
            });
        }

        if ($existing) {
            return $existing;
        }

        if (! $unitId) {
            $defaultUnit = Unit::where('code', 'NOS')->first()
                ?? Unit::where('name_en', 'like', '%Nos%')->first()
                ?? Unit::where('status', 'Active')->first()
                ?? Unit::first();
            $unitId = $defaultUnit?->id;
        }

        $finalEn = $nameEn;
        $finalTa = $nameTa;

        if (Material::where('name_en', $finalEn)->exists()) {
            $finalEn = $finalEn.' (Custom)';
        }
        if (Material::where('name_ta', $finalTa)->exists()) {
            $finalTa = $finalTa.' (புதியது)';
        }

        return Material::create([
            'name_en' => $finalEn,
            'name_ta' => $finalTa,
            'unit_id' => $unitId,
            'type' => Material::TYPE_OTHER,
            'status' => 'Active',
        ])->load('unit');
    }

    /**
     * Quick-add a new material to Master directly from Daily Allocation.
     */
    public function quickAddMaterial(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_ta' => 'nullable|string|max:255',
            'unit_id' => 'nullable|integer|exists:units,id',
        ]);

        $rawTitle = trim((string) $validated['name']);
        $unitId = ! empty($validated['unit_id']) ? (int) $validated['unit_id'] : null;
        $nameTa = ! empty($validated['name_ta']) ? trim((string) $validated['name_ta']) : null;

        $material = $this->findOrCreateMaterial($rawTitle, $unitId, $nameTa);

        return response()->json([
            'success' => true,
            'message' => "Material \"{$material->name_en}\" added to Materials Master successfully!",
            'material' => $material,
        ]);
    }

    /**
     * Quick-add a new worker category to Master directly from Daily Allocation.
     */
    public function quickAddWorkerCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name_ta' => 'required|string|max:150',
            'name_en' => 'nullable|string|max:150',
            'daily_wage' => 'required|numeric|min:0',
        ]);

        $nameTa = trim($validated['name_ta']);
        $nameEn = ! empty($validated['name_en']) ? trim($validated['name_en']) : $nameTa;

        $existingCat = WorkerCategory::where('name_ta', $nameTa)
            ->orWhere('name_en', $nameEn)
            ->first();

        if ($existingCat) {
            return response()->json([
                'success' => true,
                'message' => 'Category already exists in Master.',
                'category' => $existingCat,
            ]);
        }

        $enTaken = WorkerCategory::where('name_en', $nameEn)->exists();
        if ($enTaken && $nameEn === $nameTa) {
            $nameEn = $nameEn.' (Custom)';
        }
        $taTaken = WorkerCategory::where('name_ta', $nameTa)->exists();
        if ($taTaken && $nameEn === $nameTa) {
            $nameTa = $nameTa.' (புதியது)';
        }

        $newCat = $this->workerCategoryService->createCategory([
            'name_ta' => $nameTa,
            'name_en' => $nameEn,
            'daily_wage' => max(0, (float) $validated['daily_wage']),
            'shift' => 'Day',
            'status' => 'Active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'New worker category added to Master successfully!',
            'category' => $newCat,
        ]);
    }
}
