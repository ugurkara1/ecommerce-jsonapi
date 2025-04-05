<?php

namespace App\Http\Controllers;

use App\Models\Categories;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CategoryController extends Controller
{

    protected CategoryService $categoryService;

    public function __construct(CategoryService $categoryService){
        $this->categoryService = $categoryService;

        $this->middleware('role:super admin|admin|product manager')->only(['create', 'update']);
        $this->middleware('role:super admin|admin')->only('destroy');
    }
    // kategori listeleme (tüm kategoriler)
    public function index(Request $request)
    {
        return $this->categoryService->getAllCategories();
    }

    // Parent kategorileri alt kategoriler ile birlikte getirir
    public function getCategory()
    {
        try {
            $categories = Categories::whereNull("parent_category_id")
                ->with(['children' => function ($query) {
                    $query->orderBy('name');
                }])
                ->orderBy('name')
                ->get();

            if ($categories->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.category_not_found')
                ], 404);
            }
            return response()->json([
                'success' => true,
                'message' => __('messages.category_list'),
                'data' => $categories,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // kategori ekleme
    public function create(Request $request)
    {
        return $this->categoryService->createCategory($request);
    }

    // kategori silme
    public function destroy(Request $request, $id)
    {
        return $this->categoryService->deleteCategory($request, $id);
    }

    // kategori güncelleme
    public function update(Request $request, $id)
    {
        return $this->categoryService->updateCategory($request, $id);
    }

    // kategori filtreleme (QueryBuilder ile)
    public function show($id)
    {
        return $this->categoryService->show($id);
    }
}
