<?php

namespace App\Http\Controllers;

use App\Models\Products;
use App\Models\ProductImages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductImagesController extends Controller
{

    public function __construct()
    {
        $this->middleware('role:super admin|admin|product manager')->only(['store', 'destroy']);
    }
    /**
     * Çoklu Resim Yükleme
     */
    public function store(Request $request, Products $product)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'images' => 'required|array|max:10',
            'images.*' => 'image|mimes:jpg,png,webp|max:500',
            'variant_id' => 'nullable|exists:product_variants,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('messages.invalid_data'),
                'errors' => $validator->errors()
            ], 422);
        }

        $uploadedImages = [];
        $sortOrderBase = ProductImages::where('product_id', $product->id)->count();

        foreach ($request->file('images') as $index => $file) {
            try {
                // Resim kaydını oluştur
                $image = new ProductImages();
                $image->product_id = $product->id;
                $image->variant_id = $request->variant_id;
                $image->sort_order = $sortOrderBase + $index + 1;
                $image->save(); // İlk kayıt için ID alıyoruz

                // Dosya işlemleri
                $extension = $file->extension();
                $filename = "{$product->slug}_{$image->id}.{$extension}";
                $path = $file->storeAs('product_images', $filename, 'public');

                // Kaydı güncelle
                $image->image_url = $path;
                $image->save();

                $uploadedImages[] = $image->fresh();

            } catch (\Exception $e) {
                // Hata durumunda temizlik
                if (isset($image) && $image->id) {
                    $image->delete();
                    Storage::disk('public')->delete($path ?? '');
                }

                return response()->json([
                    'success' => false,
                    'message' => __('messages.service_error'),
                    'error' => $e->getMessage()
                ], 500);
            }
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.images_uploaded'),
            'data' => $uploadedImages
        ], 201);
    }

    /**
     * Çoklu Resim Silme
     */
    public function destroy(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'image_ids' => 'required|array|min:1',
            'image_ids.*' => 'exists:product_images,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('messages.invalid_data'),
                'errors' => $validator->errors()
            ], 422);
        }

        $images = ProductImages::whereIn('id', $request->image_ids)->get();

        foreach ($images as $image) {
            try {
                // Dosyayı sil
                Storage::disk('public')->delete($image->image_url);
                // Veritabanı kaydını sil
                $image->delete();
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.service_error'),
                    'error' => $e->getMessage()
                ], 500);
            }
        }
        activity()
        ->causedBy($user)
        ->performedOn($images->first()) // Koleksiyondan ilk öğeyi temsilci olarak kullanıyoruz
        ->withProperties(['deleted_image_ids' => $request->image_ids])
        ->log('Images deleted successfully');


        return response()->json([
            'success' => true,
            'message' => __('messages.images_deleted'),
            'deleted_count' => count($request->image_ids)
        ],200);
    }
}