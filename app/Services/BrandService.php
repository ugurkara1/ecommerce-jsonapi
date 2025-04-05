<?php
// Service Katmanı İçinde BrandService.php Dosyası

namespace App\Services;

use App\Contracts\BrandContract;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class BrandService
{
    protected BrandContract $brandRepository;

    public function __construct(BrandContract $brandRepository)
    {
        $this->brandRepository = $brandRepository;
    }

    public function getAllBrands(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => __('messages.brands_listed'),
            'data'    => $this->brandRepository->getAllBrands(),
        ]);
    }

    public function createBrand(Request $request): JsonResponse
    {
        $validated = $this->validateRequest($request);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $brand = $this->brandRepository->createBrand($validated, $request->user());

        return response()->json([ // Yanıt formatını burada yapılandırıyoruz
            'success' => true,
            'message' => __('messages.brand_created'),
            'data'    => $brand,
        ], 201);
    }

    public function updateBrand(Request $request, $id): JsonResponse
    {
        $validated = $this->validateRequest($request, $id);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $brand = $this->brandRepository->updateBrand($id, $validated, $request->user());

        if ($brand instanceof JsonResponse) {
            return $brand;
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.brand_updated'),
            'data'    => $brand,
        ], 200);
    }

    public function deleteBrand($id, $user)
    {
        // Burada marka silmeden önce ek iş mantığı eklenebilir
        // Örneğin: silinecek markanın ürünleri var mı kontrolü servis
        // katmanında da yapılabilir, böylece repository sadece veri erişim
        // işlemlerini yönetir

        return $this->brandRepository->deleteBrand($id, $user);
    }

    public function show($id): JsonResponse
    {
        // Marka ve ilişkili ürünleri almak
        $brand = $this->brandRepository->show($id);

        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => __('messages.brand_not_found'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.brand_fetched'),
            'data'    => $brand,
        ], 200);
    }


    private function validateRequest(Request $request, $id = null)
    {
        $rules = [
            'name'     => 'required|string|max:255|unique:brands,name,' . ($id ?? 'NULL') . ',id',
            'logo_url' => 'nullable|url|max:255',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('messages.invalid_data'),
                'errors'  => $validator->errors(),
            ], 422);
        }

        return $validator->validated();
    }
}