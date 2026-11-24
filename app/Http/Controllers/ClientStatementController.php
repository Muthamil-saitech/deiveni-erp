<?php

namespace App\Http\Controllers;

use App\Models\DailyAllocation;
use App\Models\ProjectClientStatement;
use App\Models\ProjectSite;
use App\Models\PurchaseOrder;
use App\Models\WeeklyWage;
use App\Services\IdCipherService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ClientStatementController extends Controller
{
    /**
     * Show the interactive client statement preparation & edit page.
     */
    public function create(Request $request, string $encryptedId): View|RedirectResponse
    {
        $siteId = IdCipherService::decrypt($encryptedId) ?: (int) $encryptedId;
        $site = ProjectSite::with(['client', 'siteCashes'])->find($siteId);

        if (! $site) {
            return redirect()->route('sites.index')->with('error', 'Project site not found.');
        }

        // 1. Gather Material Expenses (from Purchase Orders)
        $purchaseOrders = PurchaseOrder::with(['items.material.unit', 'supplier'])
            ->where('project_site_id', $site->id)
            ->where('status', '!=', 'Cancelled')
            ->orderBy('order_date', 'asc')
            ->get();

        $expenseItems = collect();

        foreach ($purchaseOrders as $po) {
            $poDate = $po->order_date ? $po->order_date->format('Y-m-d') : '';
            $supplierName = $po->supplier_name ?: ($po->supplier?->name ?? '');

            foreach ($po->items as $item) {
                $matEn = $item->material_name ?: ($item->material?->name_en ?? 'Material');
                $matTa = $item->material?->name_ta ?? '';
                $unitName = $item->unit ?: ($item->material?->unit?->name_en ?? 'Units');
                $qty = (float) $item->quantity;
                $rate = (float) $item->unit_price;
                $lineTotal = (float) $item->total_price;

                $desc = $matTa
                    ? ($matTa.' ('.$matEn.')'.($supplierName ? ' — '.$supplierName : ''))
                    : ($matEn.($supplierName ? ' — '.$supplierName : ''));

                $expenseItems->push([
                    'id' => 'mat_'.$item->id,
                    'source_type' => 'Material',
                    'is_weekly' => 0,
                    'category' => 'Material Purchase',
                    'date' => $poDate,
                    'ref_no' => $po->po_number,
                    'name_ta' => $matTa,
                    'name_en' => $matEn,
                    'supplier_name' => $supplierName,
                    'description' => $desc,
                    'quantity' => $qty,
                    'unit' => $unitName,
                    'unit_price' => $rate,
                    'original_amount' => $lineTotal,
                    'billed_amount' => $lineTotal,
                ]);
            }
        }

        // 2. Gather Labour & Wage Expenses (from Daily Allocations)
        $dailyAllocations = DailyAllocation::with(['items'])
            ->where('project_site_id', $site->id)
            ->orderBy('date', 'asc')
            ->get();

        foreach ($dailyAllocations as $alloc) {
            $allocDate = $alloc->date ? Carbon::parse($alloc->date)->format('Y-m-d') : '';

            foreach ($alloc->items as $item) {
                $tradeEn = $item->name_en ?: 'Labour Work';
                $tradeTa = $item->name_ta ?? '';
                $workerCount = (float) $item->worker_count;
                $wage = (float) $item->daily_wage;
                $subtotal = (float) $item->subtotal;

                $desc = $tradeTa
                    ? ($tradeTa.' — '.$tradeEn.' ('.(int) $workerCount.' workers)')
                    : ($tradeEn.' ('.(int) $workerCount.' workers)');

                $expenseItems->push([
                    'id' => 'lab_'.$item->id,
                    'source_type' => 'Labour',
                    'is_weekly' => 0,
                    'category' => 'Labour Work',
                    'date' => $allocDate,
                    'ref_no' => 'DA-'.$alloc->id,
                    'name_ta' => $tradeTa,
                    'name_en' => $tradeEn.' ('.(int) $workerCount.' workers)',
                    'supplier_name' => '',
                    'description' => $desc,
                    'quantity' => $workerCount,
                    'unit' => 'Workers',
                    'unit_price' => $wage,
                    'original_amount' => $subtotal,
                    'billed_amount' => $subtotal,
                ]);
            }
        }

        // Sort all date-wise entries chronologically by date
        $expenseItems = $expenseItems->sortBy('date')->values();

        // 3. Gather Weekly Expenses:
        // ONLY:
        //   a) Same-day expenses from Daily Allocations (DailyAllocationExpense)
        //   b) Weekly site expenses & settlement adjustments from Weekly Wages (WeeklyWageExpense)
        // Do not double-count or query SiteCash.

        $dailyAllocationsWithExpenses = DailyAllocation::where('project_site_id', $site->id)
            ->with('expenses')
            ->get();

        $dailyByWeek = [];
        foreach ($dailyAllocationsWithExpenses as $alloc) {
            if (! $alloc->date || $alloc->expenses->isEmpty()) {
                continue;
            }
            $d = Carbon::parse($alloc->date);
            $wStart = $d->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
            $wEnd = $d->copy()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
            $weekKey = $wStart.'_'.$wEnd;

            if (! isset($dailyByWeek[$weekKey])) {
                $dailyByWeek[$weekKey] = [
                    'start' => $wStart,
                    'end' => $wEnd,
                    'expenses' => collect(),
                    'total' => 0.0,
                ];
            }
            foreach ($alloc->expenses as $exp) {
                $amt = (float) $exp->amount;
                if ($amt > 0) {
                    $dailyByWeek[$weekKey]['expenses']->push([
                        'id' => $exp->id,
                        'name' => $exp->item_name,
                        'amount' => $amt,
                        'date' => $alloc->date,
                    ]);
                    $dailyByWeek[$weekKey]['total'] += $amt;
                }
            }
        }

        $weeklyWages = WeeklyWage::where('project_site_id', $site->id)
            ->with('expenses')
            ->get();

        $weeklyWageByWeek = [];
        foreach ($weeklyWages as $ww) {
            if (! $ww->week_start_date) {
                continue;
            }
            $wStart = Carbon::parse($ww->week_start_date)->format('Y-m-d');
            $wEnd = $ww->week_end_date ? Carbon::parse($ww->week_end_date)->format('Y-m-d') : Carbon::parse($wStart)->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
            $weekKey = $wStart.'_'.$wEnd;

            if (! isset($weeklyWageByWeek[$weekKey])) {
                $weeklyWageByWeek[$weekKey] = [
                    'start' => $wStart,
                    'end' => $wEnd,
                    'expenses' => collect(),
                    'total' => 0.0,
                ];
            }
            foreach ($ww->expenses as $wExp) {
                $amt = (float) $wExp->amount;
                if ($amt > 0) {
                    $weeklyWageByWeek[$weekKey]['expenses']->push([
                        'id' => $wExp->id,
                        'name' => $wExp->item_name,
                        'amount' => $amt,
                    ]);
                    $weeklyWageByWeek[$weekKey]['total'] += $amt;
                }
            }
        }

        $allWeekKeys = array_unique(array_merge(array_keys($dailyByWeek), array_keys($weeklyWageByWeek)));
        sort($allWeekKeys);

        $weeklyExpenses = collect();

        foreach ($allWeekKeys as $weekKey) {
            [$wStart, $wEnd] = explode('_', $weekKey);
            $startCarbon = Carbon::parse($wStart);
            $endCarbon = Carbon::parse($wEnd);
            $periodFormatted = $startCarbon->format('d/m/Y').' to '.$endCarbon->format('d/m/Y');

            $dailyTotal = $dailyByWeek[$weekKey]['total'] ?? 0.0;
            $wageTotal = $weeklyWageByWeek[$weekKey]['total'] ?? 0.0;
            $weekSum = $dailyTotal + $wageTotal;

            $detailNames = collect();
            if (isset($dailyByWeek[$weekKey])) {
                foreach ($dailyByWeek[$weekKey]['expenses'] as $de) {
                    if (! empty($de['name'])) {
                        $detailNames->push($de['name']);
                    }
                }
            }
            if (isset($weeklyWageByWeek[$weekKey])) {
                foreach ($weeklyWageByWeek[$weekKey]['expenses'] as $we) {
                    if (! empty($we['name'])) {
                        $detailNames->push($we['name']);
                    }
                }
            }
            $summaryText = $detailNames->unique()->take(4)->implode(', ');

            $weeklyExpenses->push([
                'id' => 'week_'.$startCarbon->format('Ymd'),
                'source_type' => 'WeeklyExpense',
                'is_weekly' => 1,
                'category' => 'Weekly Expenses',
                'start_date' => $wStart,
                'end_date' => $wEnd,
                'date' => $wStart,
                'ref_no' => 'WK-'.$startCarbon->format('W'),
                'name_ta' => 'வாராந்திர தள செலவுகள் ('.$startCarbon->format('d/m/Y').' முதல் '.$endCarbon->format('d/m/Y').' வரை)',
                'name_en' => 'Weekly Expenses ('.$periodFormatted.')',
                'supplier_name' => '',
                'description' => 'Weekly Site Expenses ('.$periodFormatted.')'.($summaryText ? ' — '.$summaryText : ''),
                'quantity' => 1,
                'unit' => 'Week',
                'unit_price' => $weekSum,
                'original_amount' => $weekSum,
                'billed_amount' => $weekSum,
            ]);
        }

        $weeklyExpenses = $weeklyExpenses->sortBy('start_date')->values();

        // Generate next statement number
        $prefix = 'CST-'.now()->format('Y');
        $count = ProjectClientStatement::where('statement_number', 'LIKE', "{$prefix}-%")->count();
        $nextStatementNumber = sprintf('%s-%04d', $prefix, $count + 1);

        return view('sites.client-statement', [
            'site' => $site,
            'client' => $site->client,
            'expenseItems' => $expenseItems,
            'weeklyExpenses' => $weeklyExpenses,
            'nextStatementNumber' => $nextStatementNumber,
            'todayDate' => now()->format('Y-m-d'),
        ]);
    }

    /**
     * Save the client statement snapshot.
     */
    public function store(Request $request, string $encryptedId): JsonResponse
    {
        $siteId = IdCipherService::decrypt($encryptedId) ?: (int) $encryptedId;
        $site = ProjectSite::with('client')->find($siteId);

        if (! $site) {
            return response()->json([
                'success' => false,
                'message' => 'Project site not found.',
            ], 404);
        }

        $validated = $request->validate([
            'statement_number' => ['required', 'string', 'max:50'],
            'statement_date' => ['required', 'date'],
            'title' => ['nullable', 'string', 'max:255'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'client_phone' => ['nullable', 'string', 'max:50'],
            'client_address' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'tax_percent' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $statementNumber = $validated['statement_number'];
        // Ensure statement number uniqueness
        if (ProjectClientStatement::where('statement_number', $statementNumber)->exists()) {
            $prefix = 'CST-'.now()->format('Y');
            $count = ProjectClientStatement::where('statement_number', 'LIKE', "{$prefix}-%")->count();
            $statementNumber = sprintf('%s-%04d', $prefix, $count + 1);
        }

        $statement = ProjectClientStatement::create([
            'project_site_id' => $site->id,
            'statement_number' => $statementNumber,
            'statement_date' => $validated['statement_date'],
            'title' => ! empty($validated['title']) ? $validated['title'] : 'Client Project Statement',
            'client_name' => ! empty($validated['client_name']) ? $validated['client_name'] : ($site->client?->name ?? 'Client'),
            'client_phone' => $validated['client_phone'] ?? ($site->client?->phone ?? ''),
            'client_address' => $validated['client_address'] ?? ($site->client?->address ?? ''),
            'items' => $validated['items'],
            'subtotal' => (float) $validated['subtotal'],
            'tax_percent' => (float) ($validated['tax_percent'] ?? 0),
            'tax_amount' => (float) ($validated['tax_amount'] ?? 0),
            'discount_amount' => (float) ($validated['discount_amount'] ?? 0),
            'total_amount' => (float) $validated['total_amount'],
            'notes' => $validated['notes'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Client statement saved successfully! (கிளையண்ட் அறிக்கை சேமிக்கப்பட்டது)',
            'statement_id' => $statement->id,
            'encrypted_id' => $statement->encrypted_id,
            'statement_number' => $statement->statement_number,
            'print_url' => route('sites.client-statements.print', $statement->encrypted_id),
            'site_url' => route('sites.show', $site->encrypted_id),
        ]);
    }

    /**
     * Show the printable A4 client statement view.
     */
    public function print(Request $request, string $encryptedId): View
    {
        $id = IdCipherService::decrypt($encryptedId) ?: (int) $encryptedId;
        $statement = ProjectClientStatement::with(['projectSite.client', 'creator'])->findOrFail($id);

        return view('sites.client-statement-print', [
            'statement' => $statement,
            'site' => $statement->projectSite,
            'autoprint' => $request->query('autoprint') === '1',
        ]);
    }

    /**
     * Delete a saved client statement.
     */
    public function destroy(Request $request, string $encryptedId): JsonResponse|RedirectResponse
    {
        $id = IdCipherService::decrypt($encryptedId) ?: (int) $encryptedId;
        $statement = ProjectClientStatement::findOrFail($id);
        $siteEncryptedId = $statement->projectSite?->encrypted_id;

        $statement->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Client statement deleted successfully. (அறிக்கை நீக்கப்பட்டது)',
            ]);
        }

        return redirect()->route('sites.show', $siteEncryptedId)
            ->with('success', 'Client statement deleted successfully.');
    }
}
