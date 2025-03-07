<?php

namespace App\Http\Controllers;

use App\Models\OrderProducts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\ProductVariants;

class OrderProductController extends Controller
{
    //
    public function index(){
        $orderProduct=OrderProducts::with('variant','order')->get();
        return response()->json([
            'success'=>true,
            'message'=>__('messages.order_product_listed'),
            'data'=>$orderProduct
        ]);
    }

    public function show($id){
        $orderProduct = OrderProducts::with('variant', 'order')->findOrFail($id);
        return response()->json([
            'success' => true,
            'message' => __('messages.order_product_listed'),
            'data' => $orderProduct
        ]);
    }

    public function store(Request $request){
        $user=$request->user();
        if(!$user->hasPermissionTo('manage orders')){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.unauthorized')
            ]);
        }

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
            ]);
        }
        $validatedData=$validator->validated();
        $variant=ProductVariants::findOrFail($validatedData['variant_id']);

        $validatedData['sku']=$variant->sku;

        $qrCode=$variant->qrCodes()->first();

        $validatedData['qr_code']=$qrCode->qr_image_url;
        $validatedData['base_price']=$variant->price;
        $validatedData['sale_price']=$variant->discount_price;

        $orderProduct=OrderProducts::create($validatedData);

        return response()->json([
            'success'=>true,
            'message'=>__('messages.order_product_created'),
            'data'=>$orderProduct
        ],201);
    }

    public function update(Request $request,$id){
        $user=$request->user();
        $orderProduct = OrderProducts::findOrFail($id);

        if(!$user->hasPermissionTo('manage orders')){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.unauthorized')
            ],403);
        }

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
        if (isset($validatedData['variant_id'])) {
            $variant = ProductVariants::findOrFail($validatedData['variant_id']);
            $validatedData['sku'] = $variant->sku;

            $qrCode = $variant->qrCodes()->first();
            $validatedData['qr_code']=$qrCode->qr_image_url;
        }

        $orderProduct->update($validatedData);
        return response()->json([
            'message' => 'Order product updated successfully',
            'data'    => $orderProduct
        ]);
    }
    public function destroy(Request $request,$id){
        $user=$request->user();
        if(!$user->hasPermissionTo('manage orders')){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.unauthorized')
            ],403);
        }
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
            ]);
        }catch(\Exception $e){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.service_error'),
            ],500);
        }
    }
}