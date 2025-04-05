<?php

namespace App\Services;

use App\Contracts\CategoryContract;
use App\Models\Categories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class CategoryService{
    protected $categoryRepository;

    public function __construct(CategoryContract $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function getAllCategories(){
        return response()->json([
            'success' => true,
            'message' => __('messages.categories_listed'),
            'data'    => $this->categoryRepository->getAllCategories(),
        ],200);
    }

    public function show($id){
        $category = $this->categoryRepository->show($id);
        if(!$category){
            return response()->json([
                'success' => false,
                'message' => __('messages.category_not_found'),
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => __('messages.category_show'),
            'data'    => $category,
        ]);

    }

    public function createCategory(Request $request){
        $validated=$this->validateRequest($request);
        if($validated instanceof JsonResponse){
            return $validated;
        }
        $category=$this->categoryRepository->createCategory($validated, $request->user());
        return response()->json([
            'success' => true,
            'message' => __('messages.category_created'),
            'data'    => $category,
        ], 201);
    }

    public function updateCategory(Request $request, $id){
        $validated=$this->validateRequest($request, $id);
        if($validated instanceof JsonResponse){
            return $validated;
        }
        $category=$this->categoryRepository->updateCategory($id, $validated, $request->user());
        if(!$category){ // <-- NULL olup olmadığını kontrol et
            return response()->json([
                'success' => false,
                'message' => __('messages.category_not_found'),
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => __('messages.category_updated'),
            'data'    => $category,
        ], 200);
    }

    public function deleteCategory(Request $request, $id){
        // Repository'den dönen yanıtı direkt olarak dön
        return $this->categoryRepository->deleteCategory($id, $request->user());
    }
    public function validateRequest(Request $request, $id=null){
        $rules = [
            'name' => 'required|string|max:255',
            'parent_category_id' => 'nullable|exists:categories,id',
            'slug' => 'nullable|string|unique:categories,slug',
        ];
        $validator=Validator::make($request->all(), $rules);
        if($validator->fails()){
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
        return $validator->validated();
    }
}