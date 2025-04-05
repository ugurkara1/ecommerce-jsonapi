<?php

namespace App\Http\Controllers;

use App\Models\Products;
use App\Models\ProductVariantRegion;
use App\Models\ProductVariants;
use App\Models\Regions;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ProductVariantController extends Controller
{

    public function __construct(){
        $this->middleware('role:super admin|admin|product manager')->only(['store', 'storeVariantRegion', 'update', 'destroy']);
    }
    //productvariant list
    public function index($productId)
    {
        $product = Products::findOrFail($productId);
        $variants = $product->variants()
            ->with(['product.categories', 'attributeValue','qrCodes','images'])
            ->get();

        return response()->json([
            'success'=> true,
            'messages'=> __('messages.product_variant_listed'),
            'data'=>$variants],200);
    }

    //productvariant add
    public function store(Request $request, $productId)
    {
        $user = $request->user();
        $product = Products::where('id',$productId->first());
        $validatedData = $request->validate([
            'sku' => 'required|string|unique:product_variants,sku',
            'price' => 'nullable|numeric',
            'stock' => 'required|integer|min:0',
            'attribute_value_ids' => 'required|array|min:1',
            'attribute_value_ids.*' => 'exists:attribute_values,id'
        ]);

        // Add product_id to the validated data
        $validatedData['product_id'] = $productId;

        $variant = ProductVariants::create($validatedData);
        $variant->attributeValue()->sync($validatedData['attribute_value_ids']);

        return response()->json([
            'success' => true,
            'message' => __('messages.product_variant_created'),
            'data' => $variant->load(['product', 'attributeValue']),
        ],201);
    }
    //VariantRegion
    public function storeVariantRegion(Request $request, $variantId)
    {
        $user = $request->user();
        try {
            $variant = ProductVariants::findOrFail($variantId);

            $validatedData = $request->validate([
                'region_id'      => 'required|exists:regions,id',
                'price'          => 'required|numeric',
                'discount_price' => 'nullable|numeric',
                'stock'          => 'required|integer|min:0',
            ]);

            $variantRegion = $variant->regionPricing()->create($validatedData);
            activity()
                ->causedBy($user)
                ->performedOn($variantRegion)
                ->withProperties($validatedData)
                ->log('Variant region created successfully');
            return response()->json([
                'success' => true,
                'message' => __('messages.created_variant_region'),
                'data'    => $variantRegion
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('messages.error_creating_variant_region'),
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    //Stock
    public function deducStock(int $quantity,$variantId){
        if(request()->hasHeader('Region-Id')){
            $regionId=request()->header('Region-Id');
            $regionPricing=$this->regionPricing()->where('region_id',$regionId)->first();
            if($regionPricing){
                if ($regionPricing->stock < $quantity) {
                    throw new \Exception('Seçilen bölge için yetersiz stok.');
                }
                $regionPricing->decrement('stock', $quantity);
            }else{
                throw new \Exception('Seçilen bölge için yetersiz stok');
            }
        } else {

            $$variantId->decrement('stock', $quantity);
        }
    }

    //Variant detailing show
    public function show($productId,$variantId){
        $variant=ProductVariants::with(['product.categories','qrCodes','images', 'attributeValue'])
            ->where('product_id',$productId)
            ->findOrFail($variantId);
            return response()->json([
                'success' => true,
                'message' => __('messages.product_variant_retrieved'),
                'data' => $variant
            ],200);
    }

    //Variant updated
    public function update(Request $request, $productId, $variantId)
    {
        $variant = ProductVariants::where('product_id', $productId)
            ->findOrFail($variantId);

        //Variantın eski fiyatını saklayalım
        //$oldVariantPrice=$variant->price;

        $validatedData = $request->validate([
            'sku' => 'nullable|string|unique:product_variants,sku,' . $variantId,
            'price' => 'nullable|numeric',
            'stock' => 'nullable|integer|min:0',
            'attribute_value_ids' => 'nullable|array|min:1',
            'attribute_value_ids.*' => 'exists:attribute_values,id',
            // price_percentage artık nullable: isteğe bağlı
            'price_percentage' => 'nullable|numeric',
        ]);

        $variant->update($validatedData);

        if (isset($validatedData['attribute_value_ids'])) {
            $variant->attributeValue()->sync($validatedData['attribute_value_ids']);
        }
    // Sadece price_percentage varsa ve price gönderilmemişse:
    if (isset($validatedData['price_percentage']) && !isset($validatedData['price'])) {
        // İlgili ürün varyantına ait tüm bölge kayıtlarını getir
        $regionPrices = ProductVariantRegion::where('product_variant_id', $variant->id)->get();

        foreach ($regionPrices as $regionPrice) {
            // Her bölge için ilgili Region kaydını bul
            $region = Regions::find($regionPrice->region_id);
            if ($region) {
                // Bölgenin price_percentage değerini güncelle
                $region->price_percentage = $validatedData['price_percentage'];
                $region->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Price Percentage değeri güncellendi'
        ], 200);
    }


        // Bölgesel fiyat güncelleme işlemi
        // İstekte price girilmişse veya bölgesel fiyatlarda değişiklik yapılmak isteniyorsa çalışsın
        if (isset($validatedData['price']) || true) {
            $regionPrices = ProductVariantRegion::where('product_variant_id', $variant->id)->get();

            foreach ($regionPrices as $regionPrice) {
                // Her bir bölge için ilgili Region kaydını bul
                $region = Regions::find($regionPrice->region_id);

                // Eğer istek verisinde price_percentage girilmişse kullan, aksi halde region'dan al
                if (isset($validatedData['price_percentage'])) {
                    $pricePercentage = $validatedData['price_percentage'];
                    // Eğer bölge bulunduysa, bölge kaydındaki price_percentage değerini güncelle
                    if ($region) {
                        $region->price_percentage = $pricePercentage;
                        $region->save();
                    }
                } else {
                    // İstek verisinde price_percentage yoksa, ilgili region'dan alıyoruz (null ise 0 kabul edelim)
                    $pricePercentage = $region ? $region->price_percentage : 0;
                }

                // Fiyat güncellemesi için ayarlama katsayısını hesapla
                $adjustmentFactor = 1 + ($pricePercentage / 100);
                Log::info("Region " . ($region ? $region->id : 'bilinmiyor') . " için adjustment factor: $adjustmentFactor (price_percentage: $pricePercentage)");

                // Bölgesel fiyat ve varsa indirimli fiyat güncelle
                Log::info("Eski fiyat: {$regionPrice->price}, Yeni fiyat: " . ($regionPrice->price * $adjustmentFactor));
                $regionPrice->price *= $adjustmentFactor;

                if ($regionPrice->discount_price !== null) {
                    Log::info("Eski indirimli fiyat: {$regionPrice->discount_price}, Yeni indirimli fiyat: " . ($regionPrice->discount_price * $adjustmentFactor));
                    $regionPrice->discount_price *= $adjustmentFactor;
                }

                $regionPrice->update([
                    'price' => $regionPrice->price,
                    'discount_price' => $regionPrice->discount_price,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.product_variant_updated'),
            'data' => $variant->fresh()->load(['product', 'attributeValue'])
        ], 200);
    }




    //variant delete
    public function destroy(Request $request,$productId,$variantId){
        $user = $request->user();

        $variant=ProductVariants::where('product_id',$productId)
            ->findOrFail($variantId);
        $variant->attributeValue()->detach();
        $variant->delete();


        return response()->json([
            'success' => true,
            'message' => __('messages.product_variant_deleted')
        ],200);
    }

}
