<?php

namespace App\Http\Controllers;

use App\Services\BrandService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class BrandController extends Controller
{
    protected BrandService $brandService;

    public function __construct(BrandService $brandService)
    {
        $this->brandService = $brandService;

        $this->middleware('role:super admin|admin|product manager')->only(['store', 'update']);
        $this->middleware('role:super admin|admin')->only('destroy');
    }

    public function index(): JsonResponse
    {
        return $this->brandService->getAllBrands();
    }

    public function store(Request $request): JsonResponse
    {
        return $this->brandService->createBrand($request);
    }

    public function update(Request $request, $id): JsonResponse
    {
        return $this->brandService->updateBrand($request, $id);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $result = $this->brandService->deleteBrand($id, $request->user());

        // Eğer sonuç zaten bir response ise (hata durumu), onu doğrudan döndür
        if ($result instanceof JsonResponse) {
            return $result;
        }

        // Değilse, başarılı silme işlemi
        return response()->json([
            'success' => true,
            'message' => __('messages.brand_deleted'),
        ], 200);
    }
    public function show($id): JsonResponse
    {
        return $this->brandService->show($id);
    }
}
