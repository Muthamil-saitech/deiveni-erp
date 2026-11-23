<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkerCategoryRequest;
use App\Http\Requests\UpdateWorkerCategoryRequest;
use App\Services\Contracts\WorkerCategoryServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkerCategoryController extends Controller
{
    public function __construct(
        public WorkerCategoryServiceInterface $workerCategoryService
    ) {}

    /**
     * Display a listing of the worker categories.
     */
    public function index(Request $request): View|JsonResponse
    {
        $categories = $this->workerCategoryService->getAllCategories();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $categories,
            ]);
        }

        return view('masters.worker-categories', [
            'categories' => $categories,
        ]);
    }

    /**
     * Get the next auto-generated category code.
     */
    public function nextCode(Request $request): JsonResponse
    {
        $nameEn = $request->query('name_en');
        $code = $this->workerCategoryService->generateCategoryCode($nameEn);

        return response()->json([
            'success' => true,
            'code' => $code,
        ]);
    }

    /**
     * Store a newly created worker category.
     */
    public function store(StoreWorkerCategoryRequest $request): JsonResponse|RedirectResponse
    {
        $category = $this->workerCategoryService->createCategory($request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Category \"{$category->name_ta}\" added successfully!",
                'data' => $category,
            ], 201);
        }

        return redirect()->route('worker-categories.index')
            ->with('success', "Category \"{$category->name_ta}\" added successfully!");
    }

    /**
     * Display the specified worker category.
     */
    public function show(int $id): JsonResponse
    {
        $category = $this->workerCategoryService->getCategoryById($id);

        if (! $category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $category,
        ]);
    }

    /**
     * Update the specified worker category.
     */
    public function update(UpdateWorkerCategoryRequest $request, int $id): JsonResponse|RedirectResponse
    {
        $category = $this->workerCategoryService->updateCategory($id, $request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Category \"{$category->name_ta}\" updated successfully!",
                'data' => $category,
            ]);
        }

        return redirect()->route('worker-categories.index')
            ->with('success', "Category \"{$category->name_ta}\" updated successfully!");
    }

    /**
     * Remove the specified worker category.
     */
    public function destroy(int $id): JsonResponse|RedirectResponse
    {
        $this->workerCategoryService->deleteCategory($id);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Category removed successfully.',
            ]);
        }

        return redirect()->route('worker-categories.index')
            ->with('success', 'Category removed successfully.');
    }
}
