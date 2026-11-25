<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Unit;
use App\Services\Contracts\MaterialServiceInterface;
use App\Services\Contracts\ProjectSiteServiceInterface;
use App\Services\Contracts\WeeklyWageServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WeeklyWageController extends Controller
{
    public function __construct(
        public WeeklyWageServiceInterface $weeklyWageService,
        public ProjectSiteServiceInterface $projectSiteService,
        public MaterialServiceInterface $materialService
    ) {}

    /**
     * Display the Weekly Wages Sheet page with dynamic project sites.
     */
    public function index(Request $request): View
    {
        $sites = $this->projectSiteService->getAllSites();
        $materials = Material::with('unit')
            ->where('status', 'Active')
            ->where('type', Material::TYPE_OTHER)
            ->orderBy('name_en')
            ->get();
        $units = Unit::where('status', 'Active')->orderBy('name_en')->get();

        return view('site-management.weekly-wages', compact('sites', 'materials', 'units'));
    }

    /**
     * Get weekly sheet data (daily records sync, additional expenses, summary totals).
     */
    public function getData(Request $request): JsonResponse
    {
        $request->validate([
            'project_site_id' => 'required|exists:project_sites,id',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
        ]);

        $siteId = (int) $request->input('project_site_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $data = $this->weeklyWageService->getWeeklySheet($siteId, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Save additional weekly site expenses.
     */
    public function saveExpenses(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_site_id' => 'required|exists:project_sites,id',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
            'expenses' => 'nullable|array',
            'expenses.*.item_name' => 'nullable|string|max:255',
            'expenses.*.unit_id' => 'nullable|integer|exists:units,id',
            'expenses.*.quantity' => 'nullable|numeric|min:0',
            'expenses.*.unit_price' => 'nullable|numeric|min:0',
            'expenses.*.amount' => 'nullable|numeric|min:0',
        ]);

        $siteId = (int) $validated['project_site_id'];
        $startDate = $validated['start_date'];
        $endDate = $validated['end_date'];
        $expenses = $validated['expenses'] ?? [];

        $weeklyWage = DB::transaction(function () use ($siteId, $startDate, $endDate, $expenses) {
            $this->syncOtherMaterialsFromExpenses($expenses);

            return $this->weeklyWageService->saveWeeklyExpenses($siteId, $startDate, $endDate, $expenses);
        });

        return response()->json([
            'success' => true,
            'message' => 'Weekly site expenses saved successfully.',
            'data' => $weeklyWage,
        ]);
    }

    /**
     * Approve and settle weekly wage sheet.
     */
    public function settle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_site_id' => 'required|exists:project_sites,id',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
            'expenses' => 'nullable|array',
            'expenses.*.item_name' => 'nullable|string|max:255',
            'expenses.*.unit_id' => 'nullable|integer|exists:units,id',
            'expenses.*.quantity' => 'nullable|numeric|min:0',
            'expenses.*.unit_price' => 'nullable|numeric|min:0',
            'expenses.*.amount' => 'nullable|numeric|min:0',
        ]);

        $siteId = (int) $validated['project_site_id'];
        $startDate = $validated['start_date'];
        $endDate = $validated['end_date'];

        $weeklyWage = DB::transaction(function () use ($siteId, $startDate, $endDate, $validated) {
            // If expenses are passed along during settle, save them first
            if (isset($validated['expenses'])) {
                $this->syncOtherMaterialsFromExpenses($validated['expenses']);
                $this->weeklyWageService->saveWeeklyExpenses($siteId, $startDate, $endDate, $validated['expenses']);
            }

            return $this->weeklyWageService->settleWeek(
                $siteId,
                $startDate,
                $endDate
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Weekly wages of ₹'.number_format((float) $weeklyWage->net_payout, 2).' for this week have been approved and settled successfully!',
            'data' => $weeklyWage,
        ]);
    }

    /**
     * Auto-create any new expense items in Materials Master as type "Other".
     *
     * @param  array<int, array<string, mixed>>  $expenses
     */
    private function syncOtherMaterialsFromExpenses(array $expenses): void
    {
        foreach ($expenses as $exp) {
            $rawTitle = trim((string) ($exp['item_name'] ?? ''));
            if ($rawTitle === '') {
                continue;
            }

            $unitId = ! empty($exp['unit_id']) ? (int) $exp['unit_id'] : null;
            $this->materialService->findOrCreateOtherMaterial($rawTitle, $unitId);
        }
    }
}
