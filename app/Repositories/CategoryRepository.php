<?php

namespace App\Repositories;
use App\Contracts\CategoryContract;
use App\Models\Categories;
class CategoryRepository implements CategoryContract{

	public function getAllCategories() {
        $category=Categories::with('children')->get();
        if(!$category){
            return response()->json([
                'success' => false,
                'message' => __('messages.brand_not_found'),
            ], 404);
        }
        return $category; // Alt kategorileri ilişkiyle getir

    }

    // CategoryRepository.php içinde
    public function show($id) {
        $category=Categories::with('children')->find($id);
        if(!$category){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.brand_not_found'),
            ],404);
        }
        return $category; // Alt kategorileri ilişkiyle getir
    }

	public function createCategory(array $data, $user) {
		// Implementation code
        $category = Categories::create($data);
        activity()
            ->causedBy($user)
            ->performedOn($category)
            ->withProperties(['category' => $data])
            ->log('Category created successfully');
        return $category;
	}

	public function updateCategory($id, array $data, $user) {
		// Implementation code
        $category = Categories::where('id',$id)->first();
        if(!$category){
            return null; // <-- Response değil, NULL döndür.
        }
        $category->update($data);
        activity()
            ->causedBy($user)
            ->performedOn($category)
            ->withProperties(['category' => $data])
            ->log('Category updated successfully');

        return $category;
	}

	/*public function deleteCategory($id, $user): Categories||mixed|null {
		// Implementation code
        $category = Categories::where('id',$id)->first();
        if ($category->products()->exists()) {
            return response()->json([
                'success' => false,
                'message' => __('messages.category_has_products'),
            ], 422);
        }
        $category->delete();
        activity()
            ->causedBy($user)
            ->performedOn($category)
            ->log('Category deleted successfully');
        return $category;
	}*/
    public function deleteCategory($id, $user) {
        $category = Categories::where('id', $id)->first();

        // Eğer kategori bulunamazsa 404 döndür
        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => __('messages.brand_not_found')
            ], 404);
        }

        // Eğer kategoriye bağlı ürünler varsa 422 döndür
        if ($category->products()->exists()) {
            return response()->json([
                'success' => false,
                'message' => __('messages.category_has_products')
            ], 422);
        }

        // Kategoriyi sil
        $category->delete();

        // Log kaydı ekle
        activity()
            ->causedBy($user)
            ->performedOn($category)
            ->log('Category deleted successfully');

        return response()->json([
            'success' => true,
            'message' => __('messages.category_deleted')  // "category_deleted_successfully" yerine "category_deleted" kullan
        ], 200);
    }


}