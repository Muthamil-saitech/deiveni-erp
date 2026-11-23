<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Services\Contracts\BankServiceInterface;
use App\Services\Contracts\BranchServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function __construct(
        public BranchServiceInterface $branchService,
        public BankServiceInterface $bankService
    ) {}

    /**
     * Display a listing of empanelled branches.
     */
    public function index(Request $request): View|JsonResponse
    {
        $branches = $this->branchService->getAllBranches();
        $stats = $this->branchService->getBranchStats();
        $bankList = $this->bankService->getActiveBanks();
        $allBanks = $this->bankService->getAllBanks();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $branches,
                'stats' => $stats,
                'banks' => $bankList,
                'allBanks' => $allBanks,
            ]);
        }

        return view('masters.branches', [
            'branches' => $branches,
            'stats' => $stats,
            'bankList' => $bankList,
            'allBanks' => $allBanks,
        ]);
    }

    /**
     * Store a newly created branch record.
     */
    public function store(StoreBranchRequest $request): JsonResponse|RedirectResponse
    {
        $branch = $this->branchService->createBranch($request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Branch \"{$branch->bank_name} - {$branch->branch_name}\" added successfully!",
                'data' => $branch,
                'stats' => $this->branchService->getBranchStats(),
            ], 201);
        }

        return redirect()->route('branches.index')
            ->with('success', "Branch \"{$branch->bank_name} - {$branch->branch_name}\" added successfully!");
    }

    /**
     * Display the specified branch record.
     */
    public function show(int $id): JsonResponse
    {
        $branch = $this->branchService->getBranchById($id);

        if (! $branch) {
            return response()->json([
                'success' => false,
                'message' => 'Branch record not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $branch,
        ]);
    }

    /**
     * Update the specified branch record.
     */
    public function update(UpdateBranchRequest $request, int $id): JsonResponse|RedirectResponse
    {
        $branch = $this->branchService->updateBranch($id, $request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Branch \"{$branch->bank_name} - {$branch->branch_name}\" updated successfully!",
                'data' => $branch,
                'stats' => $this->branchService->getBranchStats(),
            ]);
        }

        return redirect()->route('branches.index')
            ->with('success', "Branch \"{$branch->bank_name} - {$branch->branch_name}\" updated successfully!");
    }

    /**
     * Remove the specified branch record.
     */
    public function destroy(int $id): JsonResponse|RedirectResponse
    {
        $branch = $this->branchService->getBranchById($id);

        if (! $branch) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branch record not found.',
                ], 404);
            }

            return redirect()->route('branches.index')
                ->with('error', 'Branch record not found.');
        }

        $branchName = "{$branch->bank_name} - {$branch->branch_name}";
        $this->branchService->deleteBranch($id);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Branch \"{$branchName}\" removed successfully.",
                'stats' => $this->branchService->getBranchStats(),
            ]);
        }

        return redirect()->route('branches.index')
            ->with('success', "Branch \"{$branchName}\" removed successfully.");
    }
}
