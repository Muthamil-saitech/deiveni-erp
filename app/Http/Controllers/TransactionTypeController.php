<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionTypeRequest;
use App\Http\Requests\UpdateTransactionTypeRequest;
use App\Services\Contracts\TransactionTypeServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionTypeController extends Controller
{
    public function __construct(
        public TransactionTypeServiceInterface $transactionTypeService
    ) {}

    /**
     * Display a listing of transaction types.
     */
    public function index(Request $request): View|JsonResponse
    {
        $transactionTypes = $this->transactionTypeService->getAllTransactionTypes();
        $stats = $this->transactionTypeService->getStats();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $transactionTypes,
                'stats' => $stats,
            ]);
        }

        return view('masters.transaction-types', [
            'transactionTypes' => $transactionTypes,
            'stats' => $stats,
        ]);
    }

    /**
     * Store a newly created transaction type.
     */
    public function store(StoreTransactionTypeRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $type = $this->transactionTypeService->createTransactionType($request->validated());

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Transaction Type \"{$type->name_en}\" created successfully!",
                    'data' => $type,
                    'stats' => $this->transactionTypeService->getStats(),
                ], 201);
            }

            return redirect()->route('transaction-types.index')
                ->with('success', "Transaction Type \"{$type->name_en}\" created successfully!");
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withInput()->withErrors(['code' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified transaction type.
     */
    public function show(int $id): JsonResponse
    {
        $type = $this->transactionTypeService->getTransactionTypeById($id);

        if (! $type) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction type record not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $type,
        ]);
    }

    /**
     * Update the specified transaction type.
     */
    public function update(UpdateTransactionTypeRequest $request, int $id): JsonResponse|RedirectResponse
    {
        try {
            $type = $this->transactionTypeService->updateTransactionType($id, $request->validated());

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Transaction Type \"{$type->name_en}\" updated successfully!",
                    'data' => $type,
                    'stats' => $this->transactionTypeService->getStats(),
                ]);
            }

            return redirect()->route('transaction-types.index')
                ->with('success', "Transaction Type \"{$type->name_en}\" updated successfully!");
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withInput()->withErrors(['code' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified transaction type.
     */
    public function destroy(Request $request, int $id): JsonResponse|RedirectResponse
    {
        try {
            $type = $this->transactionTypeService->getTransactionTypeById($id);

            if (! $type) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Transaction type record not found.',
                    ], 404);
                }

                return redirect()->route('transaction-types.index')->with('error', 'Transaction type record not found.');
            }

            $name = $type->name_en;
            $this->transactionTypeService->deleteTransactionType($id);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Transaction Type \"{$name}\" deleted successfully!",
                    'stats' => $this->transactionTypeService->getStats(),
                ]);
            }

            return redirect()->route('transaction-types.index')
                ->with('success', "Transaction Type \"{$name}\" deleted successfully!");
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->route('transaction-types.index')->with('error', $e->getMessage());
        }
    }
}
