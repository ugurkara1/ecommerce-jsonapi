<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderProducts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\ProductVariants;
use App\Models\Campaigns;
use App\Models\ProductVariantRegion;

class OrderProductController extends Controller
{
    //
    public function __construct()
    {
        // Tüm endpointlere erişim yalnızca "super admin", "admin" ve "order manager" rollerine sahip kullanıcılarla sınırlandırıldı.
        $this->middleware('role:super admin|admin|order manager');
    }
    public function index(){

        $orderProduct=OrderProducts::with('variant','order')->get();
        return response()->json([
            'success'=>true,
            'message'=>__('messages.order_product_listed'),
            'data'=>$orderProduct
        ],200);
    }

    public function show($id){

        $orderProduct = OrderProducts::with('variant', 'order')->findOrFail($id);
        return response()->json([
            'success' => true,
            'message' => __('messages.order_product_showed'),
            'data' => $orderProduct
        ],200);
    }

    public function store(Request $request){
        $user = request()->user();

        $validator=Validator::make($request->all(),[
            'order_id'=>'required|exists:orders,id',
            'variant_id'=>'required|exists:product_variants,id',
            'quantity'=>'required|integer:min:1',
            'quantity_type'=>'required|in:adet,kg',
            //'base_price'=>'required|numeric',
            //'sale_price'=>'nullable|numeric',
            'tax_rate'=>'nullable|numeric',//vergi oranı
            'gift_package'=>'nullable|boolean',
            'region_id'=>'nullable|exists:regions,id'
        ]);

        if($validator->fails()){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.invalid_data')
            ],422);
        }
        $validatedData=$validator->validated();
        $order = Order::find($validatedData['order_id']);
        /*$currentProcess=$order->orderProcesses()->latest()->first();
        if(!$currentProcess || $currentProcess->status != 'Sipariş Oluşturma'){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.only_allowed_in_creation_phase')
            ],403);
        }*/
        // Doğru ilişkiyi kullanarak
        $currentProcess = $order->process; // OrderProcess nesnesi dönecektir.

        if ($currentProcess && $currentProcess->name != "Order Creation") {
            return response()->json([
                'success' => false,
                'message' => __('messages.only_allowed_in_creation_phase')
            ], 403);
        }

        $variant=ProductVariants::findOrFail($validatedData['variant_id']);

        $validatedData['sku']=$variant->sku;

        $qrCode=$variant->qrCodes()->first();

        $validatedData['qr_code']=$qrCode->qr_image_url;
        $validatedData['base_price']=$variant->price;
        $validatedData['sale_price']=$variant->discount_price;
        /*//$stockupdated=($variant->stock)-$validatedData['quantity'];
        if(($variant->stock)<0){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.stock_not_found')
            ]);
        }*/

        if(isset($validatedData['region_id'])){
            $regionalStock=ProductVariantRegion::where([
                ['product_variant_id',$validatedData['variant_id']],
                ['region_id',$validatedData['region_id']]
            ])->first();
            $validatedData['base_price']=$regionalStock->price;
            $validatedData['sale_price']=$regionalStock->discount_price;
            if(!$regionalStock){
                return response()->json([
                    'success' => false,
                    'message' => __('messages.region_stock_not_found')
                ], 404);
            }
            $stockUpdated=$regionalStock->stock - $validatedData['quantity'];
            if ($stockUpdated < 0) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.insufficient_stock')
                ], 422);
            }
            $regionalStock->update(['stock'=>$stockUpdated]);
        }else{
            $validatedData['base_price']=$variant->price;
            $validatedData['sale_price']=$variant->discount_price;
                    // Varsayılan varyant stoğu kontrolü
            $stockUpdated = $variant->stock - $validatedData['quantity'];

            if ($stockUpdated < 0) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.insufficient_stock')
                ], 422);
            }

            // Varsayılan stok güncelleme
            $variant->update(['stock' => $stockUpdated]);
        }
        /*
        $stockupdated=($variant->stock)-$validatedData['quantity'];
        if($stockupdated<0){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.stock_not_found')
            ],422);
        }

        $variant->update([
            'stock'=>$stockupdated
        ]);
        */
        $orderProduct=OrderProducts::create($validatedData);

        //sipariş fiyatını güncellemek için
        $this->recalOrderTotals($validatedData['order_id']);

        $functionName=__FUNCTION__;
        activity()
            ->causedBy($user)
            ->performedOn($orderProduct)
            ->withProperties($validatedData)
            ->log("Function name: $functionName. Order Product created");


        return response()->json([
            'success'=>true,
            'message'=>__('messages.order_product_created'),
            'data'=>$orderProduct
        ],201);
    }

    public function update(Request $request,$id){
        $user=$request->user();
        $orderProduct = OrderProducts::where('id',$id)->first();


        $validator=Validator::make($request->all(),[
            'order_id'=>'required|exists:orders,id',
            'variant_id'=>'required|exists:product_variants,id',
            'quantity'=>'required|integer:min:1',
            'quantity_type'=>'required|in:adet,kg',
            //'base_price'=>'required|numeric',
            //'sale_price'=>'nullable|numeric',
            'tax_rate'=>'nullable|numeric',
            'gift_package'=>'nullable|boolean'
        ]);
        if($validator->fails()){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.invalid_data')
            ],422);
        }
        $validatedData=$validator->validated();
        //Eğer variant bilgisi güncelleniyorsa
        /*if (isset($validatedData['variant_id'])) {
            $variant = ProductVariants::findOrFail($validatedData['variant_id']);
            $validatedData['sku'] = $variant->sku;

            $qrCode = $variant->qrCodes()->first();
            $validatedData['qr_code']=$qrCode->qr_image_url;
        }*/

        if($orderProduct->variant_id !== $validatedData['variant_id']){
            //Eski varyantın stoğunu eski miktar kadar artırdık
            $oldVariant = ProductVariants::findOrFail($orderProduct->variant_id);
            $oldVariant->increment('stock', $orderProduct->quantity);

            //Yeni varyantın stoğunu güncelle
            $newVariant = ProductVariants::findOrFail($validatedData['variant_id']);
            if($newVariant->stock<$validatedData['quantity']){
                return response()->json([
                    'success'=>false,
                    'message'=>__('messages.stock_not_found')
                ],422);
            }
            $newVariant->decrement('stock', $validatedData['quantity']);


            // Yeni varyant bilgilerini güncelle
            $validatedData['sku'] = $newVariant->sku;
            $qrCode = $newVariant->qrCodes()->first();
            $validatedData['qr_code'] = $qrCode ? $qrCode->qr_image_url : null;
            $validatedData['base_price'] = $newVariant->price;
            $validatedData['sale_price'] = $newVariant->discount_price;
        }else{
            // Varyant aynıysa, stok güncellemesi yap
            $variant = ProductVariants::findOrFail($orderProduct->variant_id);
            $quantityDifference = $validatedData['quantity'] - $orderProduct->quantity;

            if ($variant->stock < $quantityDifference) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.stock_not_found')
                ], 422);
            }

            $variant->decrement('stock', $quantityDifference);
        }

        $orderProduct->update($validatedData);

        $functionName=__FUNCTION__;
        activity()
            ->causedBy($user)
            ->performedOn($orderProduct)
            ->withProperties($validatedData)
            ->log("Function name: $functionName. Order Product updated");

        $this->recalOrderTotals($validatedData['order_id']);
        return response()->json([
            'message' => 'Order product updated successfully',
            'data'    => $orderProduct,
        ]);
    }
    public function destroy(Request $request,$id){
        $user = request()->user();

        try{
            $orderProduct=OrderProducts::where('id',$id)->first();
            if(!$orderProduct){
                return response()->json([
                    'success'=>false,
                    'message'=>__('messages.orderProduct_not_found')
                ]);
            }
            $orderProductData=[
                'id'=>$orderProduct->id,
                'order_id'=>$orderProduct->order_id,
                'quantity'=>$orderProduct->quantity,
                'quantity_type'=>$orderProduct->quantity_type,
                'base_price'=>$orderProduct->base_price,
                'sale_price'=>$orderProduct->sale_price,
                'tax_rate'=>$orderProduct->tax_rate,
                'gift_package'=>$orderProduct->gift_package,
                'sku'=>$orderProduct->sku,
                'qr_code'=>$orderProduct->qr_code,
            ];

            //Eğer sipariş edilen ürün silindiyse stock miktarını güncelle
            $variant=ProductVariants::findOrFail($orderProduct->variant_id);
            $variant->increment('stock',$orderProduct->quantity);
            $functionName=__FUNCTION__;

            activity()
                ->causedBy($user)
                ->performedOn($orderProduct)
                ->withProperties(['attributes'=>$orderProductData])
                ->log("Function Name: $functionName. Order Product deleted");
            $orderProduct->delete();


            return response()->json([
                'success'=>true,
                'data'=>$orderProduct,
                'message'=>__('messages.order_deleted')
            ],200);
        }catch(\Exception $e){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.service_error'),
            ],500);
        }
    }

    //Belirtilen sipariş için tüm sipariş ürünleri üzerinden alt toplam,vergi,kargo ve toplam tutarını yeniden hesaplar

    private function recalOrderTotals($orderId)
    {
        $order = Order::find($orderId);
        if (!$order) {
            return;
        }

        // Siparişe ait tüm ürünleri alıyoruz
        $orderProducts = OrderProducts::where('order_id', $orderId)->get();

        $subTotal = 0;
        $tax = 0;

        foreach ($orderProducts as $op) {
            $price = (!empty($op->sale_price) && $op->sale_price) ? $op->sale_price : $op->base_price;
            $lineTotal = $price * $op->quantity;
            $subTotal += $lineTotal;

            // Vergiyi her ürün için toplayarak hesaplıyoruz
            if ($op->tax_rate) {
                $tax += $lineTotal * ($op->tax_rate / 100);
            }
        }

        // Kargo maliyeti, her ürün için 5 TL olarak hesaplanıyor
        $shippingCost = count($orderProducts) * 5;
        $total = $subTotal + $tax + $shippingCost;

        // Kampanya kontrolü: Toplam tutar 500 TL veya üzeriyse free_shipping kampanyası uygulanır
        $campaignId = null;
        if ($total >= 500) {
            $campaign = Campaigns::where('campaign_type', 'free_shipping')
                ->where('is_active', true)
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now())
                ->first();

            if ($campaign) {
                $shippingCost = 0; // Kargo bedava
                $campaignId = $campaign->id;
                // Toplamı, kargo bedava olarak yeniden hesaplıyoruz
                $total = $subTotal + $tax + $shippingCost;
            }
        }

        $order->update([
            'subtotal'     => $subTotal,
            'tax_amount'   => $tax,
            'shipping_cost'=> $shippingCost,
            'total_amount' => $total,
            'campaign_id'  => $campaignId
        ]);
    }

}
