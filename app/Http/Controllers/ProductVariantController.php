<?php

namespace App\Http\Controllers;

use App\Models\Products;
use App\Models\ProductVariants;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductVariantController extends Controller
{
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
            'data'=>$variants]);
    }

    //productvariant add
    public function store(Request $request, $productId)
    {
        $user = $request->user();
        if (!$user->hasPermissionTo('manage product variants')) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized')
            ], 401);
        }

        $product = Products::findOrFail($productId);
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
        ], Response::HTTP_CREATED);
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
            ]);
    }

    //Variant updated
    public function update(Request $request,$productId,$variantId){

        $user=$request->user();
        if(!$user->hasPermissionTo('manage product variants')){
            return response()->json([
                'success'=>false,
                'message'=> __('messages.unauthorized')
            ],401);
        }


        $variant=ProductVariants::where('product_id',$productId)
            ->findOrFail($variantId);

        $validatedData = $request->validate([
            'sku'=> 'required|string|unique:product_variants,sku,'.$variantId,
            'price'=>'nullable|numeric',
            'stock'=>'required|integer|min:0',
            'attribute_value_ids'=> 'sometimes|array|min:1',
            'attribute_value_ids.*'=> 'exists:attribute_values,id'
        ]);

        $variant->update($validatedData);
        if(isset($validatedData['attribute_value_ids'])){
            $variant->attributeValue()->sync($validatedData['attribute_value_ids']);
        }
        return response()->json([
            'success' => true,
            'message' => __('messages.product_variant_updated'),
            'data' => $variant->fresh()->load(['product', 'attributeValue'])
        ]);

    }

    //variant delete
    public function destroy(Request $request,$productId,$variantId){
        $user=$request->user();
        if(!$user->hasPermissionTo('manage product variants')){
            return response()->json([
                'success'=>false,
                'message'=> __('messages.unauthorized')
            ],401);
        }
        $variant=ProductVariants::where('product_id',$productId)
            ->findOrFail($variantId);
        $variant->attributeValue()->detach();
        $variant->delete();


        return response()->json([
            'success' => true,
            'message' => __('messages.product_variant_deleted')
        ], Response::HTTP_NO_CONTENT);
    }

}