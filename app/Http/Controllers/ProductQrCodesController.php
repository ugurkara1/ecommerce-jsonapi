<?php

namespace App\Http\Controllers;

use App\Models\ProductQrCodes;
use App\Models\Products;
use App\Models\ProductVariants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ProductQrCodesController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super admin|admin|product manager')->only(['store', 'destroy']);
    }
    public function index($productVariantId)
    {
        $productVariant = ProductVariants::with("qrCodes")->findOrFail($productVariantId);
        return response()->json($productVariant->qrCodes);
    }

    public function store(Request $request, $product_variant_id)
    {
        $user = $request->user();

        $productVariant = ProductVariants::with('product')->findOrFail($product_variant_id);

        $productSlug = $productVariant->product->slug;

        // Generate variant-specific URL with product slug
        $qrData = config('app.url') . "/urun-detay/{$productSlug}?variant={$productVariant->id}";
        //$qrData = config('app.url') . "/urun-detay/{$productVariant->slug}";

        // QR kodu görüntüsünü oluştur ve kaydet
        $fileName = 'qrcodes/' . Str::uuid() . '.svg';

        QrCode::size(400)
            ->format('svg')
            ->backgroundColor(255, 255, 255, 0)
            ->color(...sscanf($request->onplan_rengi ?? '#000000', "#%02x%02x%02x"))
            ->generate($qrData, Storage::disk('public')->path($fileName));

        // QR kod modelini oluştur
        $qrCode = new ProductQrCodes([
            'qr_data' => $qrData,
            'qr_image_url' => Storage::url($fileName),
            'olusturulma_tarihi' => now(),
        ]);

        $productVariant->qrCodes()->save($qrCode);

        return response()->json([
            'success' => true,
            'message' => __('messages.qr_code_created'),
            'qr_id' => $qrCode->id,
            'qr_data' => $qrCode->qr_data,
            'qr_image_url' => $qrCode->qr_image_url
        ],201);
    }
    public function destroy(Request $request, $productVariantId,$qrCodeId)
    {
        $user = $request->user();

        $qrCode = ProductQrCodes::where('product_variant_id', $productVariantId)
            ->findOrFail($qrCodeId);
        $qrCode->delete();

        return response()->json([
            'success' => true,
            'message' => __('messages.qr_code_deleted'),
        ],200);
    }
    public function show($productVariantId,$qrCodeId){
        $qrCode=ProductQrCodes::where('product_id',$productVariantId)->findOrFail($qrCodeId);

        return QrCode::size(300)
            ->format('svg')
            ->backgroundColor(255, 255, 255, 0)
            ->color(0, 0, 0)
            ->generate($qrCode->qr_data);

    }
}
