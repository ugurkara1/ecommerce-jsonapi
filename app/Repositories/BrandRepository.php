<?php

namespace App\Repositories;

use App\Contracts\BrandContract;
use App\Models\Brands;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BrandRepository implements BrandContract
{
    public function getAllBrands()
    {
        $brand=Brands::all();
        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => __('messages.brand_not_found'),
            ], 404);
        }
        return $brand;
    }

    public function createBrand(array $data, $user)
    {
        $brand = Brands::create($data);
        activity()
            ->causedBy($user)
            ->performedOn($brand)
            ->withProperties(['brand' => $data])
            ->log('Brand created successfully');
        return $brand;
    }

    public function updateBrand($id, array $data, $user)
    {
        $brand = Brands::where('id',$id)->first(); // Use find() for cleaner code

        // Check if brand exists
        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => __('messages.brand_not_found'),
            ], 404);
        }

        // Check if brand has products
        if ($brand->products()->exists()) {
            return response()->json([
                'success' => false,
                'message' => __('messages.brand_has_products'),
            ], 422);
        }

        // Proceed with update
        $brand->update($data);
        activity()
            ->causedBy($user)
            ->performedOn($brand)
            ->withProperties(['brand' => $data])
            ->log('Brand updated successfully');
        return $brand;
    }

    public function deleteBrand($id, $user)
    {
        try {
            $brand = Brands::findOrFail($id);

            if ($brand->products()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.brand_has_products'),
                ], 422);
            }

            $brand->delete();
            activity()
                ->causedBy($user)
                //->performedOn($brand)
                ->log('Brand deleted successfully');
            return $brand;
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => __('messages.brand_not_found'),
            ], 404);
        }
    }
    public function show($id)
    {
        // Markayı ve ilişkili ürünlerini alıyoruz
        $brand=Brands::with('products')->where('id', $id)->first();
        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => __('messages.brand_not_found'),
            ], 404);
        }
        return $brand;
    }

}