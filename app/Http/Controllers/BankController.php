<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBankRequest;
use App\Http\Requests\UpdateBankRequest;
use App\Services\Contracts\BankServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BankController extends Controller
{
    public function __construct(
        public BankServiceInterface $bankService
    ) {}

    /**
     * Display a listing of empanelled banks.
     */
    public function index(Request $request): View|JsonResponse
    {
        $banks = $this->bankService->getAllBanks();
        $stats = $this->bankService->getBankStats();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $banks,
                'stats' => $stats,
            ]);
        }

        return view('masters.banks', [
            'banks' => $banks,
            'stats' => $stats,
        ]);
    }

    /**
     * Store a newly created bank record.
     */
    public function store(StoreBankRequest $request): JsonResponse|RedirectResponse
    {
        $bank = $this->bankService->createBank($request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Bank \"{$bank->bank_name}\" added successfully!",
                'data' => $bank,
                'stats' => $this->bankService->getBankStats(),
            ], 201);
        }

        return redirect()->route('banks.index')
            ->with('success', "Bank \"{$bank->bank_name}\" added successfully!");
    }

    /**
     * Display the specified bank record.
     */
    public function show(int $id): JsonResponse
    {
        $bank = $this->bankService->getBankById($id);

        if (! $bank) {
            return response()->json([
                'success' => false,
                'message' => 'Bank record not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $bank,
        ]);
    }

    /**
     * Update the specified bank record.
     */
    public function update(UpdateBankRequest $request, int $id): JsonResponse|RedirectResponse
    {
        $bank = $this->bankService->updateBank($id, $request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Bank \"{$bank->bank_name}\" updated successfully!",
                'data' => $bank,
                'stats' => $this->bankService->getBankStats(),
            ]);
        }

        return redirect()->route('banks.index')
            ->with('success', "Bank \"{$bank->bank_name}\" updated successfully!");
    }

    /**
     * Remove the specified bank record.
     */
    public function destroy(int $id): JsonResponse|RedirectResponse
    {
        $bank = $this->bankService->getBankById($id);

        if (! $bank) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bank record not found.',
                ], 404);
            }

            return redirect()->route('banks.index')
                ->with('error', 'Bank record not found.');
        }

        if ($this->bankService->isBankInUse($id)) {
            $message = "Bank \"{$bank->bank_name}\" cannot be deleted because it is currently used in Branch Master (இந்த வங்கி ஏற்கனவே கிளைகளில் பயன்படுத்தப்படுவதால் நீக்க முடியாது).";

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            return redirect()->route('banks.index')
                ->with('error', $message);
        }

        $bankName = $bank->bank_name;
        $this->bankService->deleteBank($id);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Bank \"{$bankName}\" removed successfully.",
                'stats' => $this->bankService->getBankStats(),
            ]);
        }

        return redirect()->route('banks.index')
            ->with('success', "Bank \"{$bankName}\" removed successfully.");
    }
}
