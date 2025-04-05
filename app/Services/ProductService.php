<?php

namespace App\Services;
use App\Contracts\ProductContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductService
{
    protected $productRepository;

    public function __construct(ProductContract $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function getAll()
    {
        return response()->json([
            'success' => true,
            'message' => __('messages.products_listed'),
            'data' => $this->productRepository->getAll()
        ], 200);
    }
    public function show($id)
    {
        $product = $this->productRepository->show($id);
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => __('messages.product_not_found'),
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => __('messages.product_show'),
            'data' => $product,
        ]);
    }

    public function create(Request $request)
    {
        $validated = $this->validateRequest($request);
        $user = $request->user(); // Doğru: Authenticatable instance

        if ($validated instanceof JsonResponse) {
            return $validated;
        }
        $product = $this->productRepository->create($validated, $request->user());
        return response()->json([
            'success' => true,
            'message' => __('messages.product_created'),
            'data' => $product,
        ], 201);
    }

    public function validateRequest(Request $request, $id = null)
    {
        $rules = [
            'sku' => 'required|string|unique:products,sku' . ($id ? ",{$id}" : ''),
            'name' => 'required|string',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'brand_id' => 'required|exists:brands,id',
            'is_active' => 'sometimes|boolean',
            'slug' => 'nullable|string|unique:products,slug' . ($id ? ",{$id}" : ''),
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }
        return $request->validate($rules);
    }

    public function update(Request $request, $id)
    {
        try {
            $product = $this->productRepository->show($id);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => __('messages.product_not_found'),
            ], 404);
        }

        $validated = $this->validateRequest($request, $id);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $product = $this->productRepository->update($id, $validated, $request->user());

        return response()->json([
            'success' => true,
            'message' => __('messages.product_updated'),
            'data'    => $product,
        ], 200);
    }

    // This method is removed because it is a duplicate

    public function delete(Request $request, $id)
    {
        try {
            $product = $this->productRepository->show($id);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => __('messages.product_not_found'),
            ], 404);
        }

        // Eğer repository.delete() içinde de findOrFail varsa, aynı şekilde yakalayın
        $this->productRepository->delete($id, $request->user());

        return response()->json([
            'success' => true,
            'message' => __('messages.product_deleted'),
        ], 200);
    }
}
