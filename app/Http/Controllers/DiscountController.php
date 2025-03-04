<?php

namespace App\Http\Controllers;

use App\Models\Discounts;
use App\Models\Products;
use App\Models\ProductVariants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DiscountController extends Controller
{
    public function index(){
        $discounts=Discounts::with(['categories','products','variants','brands'])->get();
        return response()->json([
            'success'=>true,
            'message'=>__('messages.discounts'),
            'data'=>$discounts,
        ],200);
    }


    //discount create
    public function store(Request $request){
        $user=$request->user();
        if(!$user->hasPermissionTo('manage discounts')){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.unauthorized'),
            ],401);
        }

        $validator=Validator::make($request->all(), [
            'name'=> 'required|string|max:255',
            'discount_type'=>'sometimes|required|in:percentage,fixed,buy_x_get_y',
            'value'=>'sometimes|required|numeric|min:0',
            'start_date'=> 'nullable|date',
            'end_date'=> 'nullable|date',
            'applies_to'=> 'sometimes|required|in:all,categories,products,variants,brands',
            'is_active'=> 'sometimes|boolean',
            'category_ids'=> 'required_if:applies_to,categories|array',
            'category_ids.*'=> 'exists:categories,id',
            'product_ids'=> 'required_if:applies_to,products|array',
            'product_ids.*'=> 'exists:products,id',
            'variant_ids'=> 'required_if:applies_to,variants|array',
            'variant_ids.*'=> 'exists:product_variants,id',
            'brand_ids'=>'required_if:applies_to,brands|array',
            'brands_ids.*'=>'exists:brands,id'
        ]);

        if($validator->fails()){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.invalid_data'),
                'errors'=>$validator->errors(),
            ],422);
        }

        $data=$validator->validated();

        //create discount
        $discount=Discounts::create([
            'name'=> $data['name'],
            'discount_type'=> $data['discount_type'],
            'value'=> $data['value'],
            'start_date'=> $data['start_date'],
            'end_date'=> $data['end_date'],
            'applies_to'=> $data['applies_to'],
            'is_active'=> $data['is_active'] ?? true,
        ]);

        //level at which discount is applies

        if ($data['applies_to'] === 'categories' && isset($data['category_ids'])) {
            $discount->categories()->sync($data['category_ids']);
            $this->updateDiscountPricesByCategories($data['category_ids'], $discount);
        }
        else if($data['applies_to'] === 'products'&& isset($data['product_ids'])){
            $discount->products()->sync($data['product_ids']);
            $this->updateDiscountPricesByProducts($data['product_ids'], $discount);
        }
        else if($data['applies_to'] === 'variants'&& isset($data['variant_ids'])){
            $discount->variants()->sync($data['variant_ids']);
            $this->updateDiscountPricesByVariants($data['variant_ids'], $discount);
        }
        else if($data['applies_to'] === 'brands'&& isset($data[ 'brand_ids'])){
            $discount->brands()->sync($data['brand_ids']);
            $this->updateDiscountPricesByBrands($data['brand_ids'],$discount);
        }

        return response()->json([
            'success'=>true,
            'message'=>__('messages.discount_created'),
            'data'=>$discount->load(['categories','products','variants','brands']),
        ],201);
    }
    private function updateDiscountPricesByCategories($categoryIds,$discount){
        $products = Products::whereHas('categories', function ($query) use ($categoryIds) {
            $query->whereIn('categories.id', $categoryIds);
        })->get();

        foreach ($products as $product){
            $this->applyDiscount($product, $discount);
        }

        $productIds = $products->pluck('id')->toArray();
        $variants=ProductVariants::whereIn('product_id',$productIds)->get();
        // Tüm variantlara indirim uyguluyoruz.
        foreach ($variants as $variant) {
            $this->applyDiscountToVariant($variant, $discount);
        }
    }
    private function updateDiscountPricesByProducts($productIds,$discount){
        $products=Products::whereIn('id',$productIds)->get();
        foreach ($products as $product){
            $this->applyDiscount($product, $discount);
        }
        $productIds = $products->pluck('id')->toArray();
        $variants=ProductVariants::whereIn('product_id',$productIds)->get();
        foreach ($variants as $variant){
            $this->applyDiscountToVariant($variant, $discount);
        }

    }
    private function updateDiscountPricesByVariants($variantIds,$discount){
        $variants=ProductVariants::whereIn('id',$variantIds)->get();
        foreach ($variants as $variant){
            $this->applyDiscount($variant, $discount);
        }
    }
    private function updateDiscountPricesByBrands($brandIds,$discount){
        $products = Products::whereHas('brands', function ($query) use ($brandIds): void {
            $query->whereIn('brands.id', $brandIds);
        })->get();

        foreach ($products as $product){
            $this->applyDiscount($product, $discount);
        }

        $productIds = $products->pluck('id')->toArray();
        $variants=ProductVariants::whereIn('product_id',$productIds)->get();
        // Tüm variantlara indirim uyguluyoruz.
        foreach ($variants as $variant) {
            $this->applyDiscountToVariant($variant, $discount);
        }
    }

    private function applyDiscount($product, $discount) {
        if ($discount->discount_type === 'percentage') {
            $discountedPrice = $product->price * (1 - $discount->value / 100);
        } else {
            $discountedPrice = max($product->price - $discount->value, 0);
        }
        $product->update(['discount_price' => $discountedPrice]);

        if ($product->variants && $product->variants->isNotEmpty()) {
            foreach ($product->variants as $variant) {
                $this->applyDiscountToVariant($variant, $discount);
            }
        } else {
            return response()->json([
                'success'=>false,
                'message'=>__('messages.no_variant_found'),
            ],404);
        }
    }

    private function applyDiscountToVariant($variant, $discount) {
        if ($discount->discount_type === 'percentage') {
            $discountedPrice = $variant->price * (1 - $discount->value / 100);
        } else {
            $discountedPrice = max($variant->price - $discount->value, 0);
        }
        $variant->update(['discount_price' => $discountedPrice]);
    }
    //discount update
    public function update(Request $request, $id){
        $user=$request->user();
        if(!$user->hasPermissionTo('manage discounts')){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.unauthorized'),
            ],401);
        }
        $discount=Discounts::where('id',$id)->first();

        if(!$discount){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.discount_not_found'),
            ],404);
        }

        $validator=Validator::make($request->all(), [
            'name'=> 'required|string|max:255',
            'discount_type'=>'sometimes|required|in:percentage,fixed,buy_x_get_y',
            'value'=>'sometimes|required|numeric|min:0',
            'star_date'=> 'nullable|date',
            'end_date'=> 'nullable|date',
            'applies_to'=> 'sometimes|required|in:all,categories,products,variants',
            'is_active'=> 'sometimes|boolean',
            'category_ids'=> 'required_if:applies_to,categories|array',
            'category_ids.*'=> 'exists:categories,id',
            'product_ids'=> 'required_if:applies_to,products|array',
            'product_ids.*'=> 'exists:products,id',
            'variant_ids'=> 'required_if:applies_to,variants|array',
            'variant_ids.*'=> 'exists:product_variants,id',
        ]);

        if($validator->fails()){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.invalid_data'),
                'errors'=>$validator->errors(),
            ],422);
        }

        $data=$validator->validated();

        //discount record update
        $discount->update([
            'name'         => $data['name'] ?? $discount->name,
            'discount_type'=> $data['discount_type'] ?? $discount->discount_type,
            'value'        => $data['value'] ?? $discount->value,
            'start_date'   => $data['start_date'] ?? $discount->start_date,
            'end_date'     => $data['end_date'] ?? $discount->end_date,
            'applies_to'   => $data['applies_to'] ?? $discount->applies_to,
            'is_active'    => $data['is_active'] ?? $discount->is_active,
        ]);

        //pivot table update

        if(isset($data['applies_to'])){
            if($data['applies_to'] === 'categories' && isset($data['category_ids'])){
                $discount->categories()->sync($data['category_ids']);
                $this->updateDiscountPricesByCategories($data['category_ids'], $discount);
            }else if($data['applies_to'] === 'products'&& isset($data['product_ids'])){
                $discount->products()->sync($data['product_ids']);
                $this->updateDiscountPricesByProducts($data['product_ids'], $discount);
            }
            else if($data['applies_to'] === 'variants'&& isset($data['variant_ids'])){
                $discount->variants()->sync($data['variant_ids']);
                $this->updateDiscountPricesByVariants($data['variant_ids'], $discount);
            }

        }

        return response()->json([
            'success'=> true,
            'message'=> __('messages.discount_updated'),
            'data'=> $discount->load(['categories','products','variants']),
        ],200);
    }
    public function endDiscount(Request $request,$discountId){
        $user=$request->user();
        if(!$user->hasPermissionTo('add products')){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.unauthorized'),
            ],401);
        }
        $discount = Discounts::with(['products', 'categories', 'variants','brands'])->find($discountId);
        if(!$discount){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.discount_not_found'),
            ],404);
        }
        $discount->update([
            'is_active'=>false,
        ]);
        //indirimin hangi bazda uygulandığına bağlı olarak indirimi iptal ediyoruz
        switch( $discount->applies_to ){
            case 'products':
                foreach($discount->products as $product){
                    $product->update(['discount_price'=>null]);
                }
                break;
            case 'categories':
                foreach($discount->categories as $category) {
                    $products = Products::whereHas('categories', function ($query) use ($category) {
                        $query->where('categories.id', $category->id);
                    })->get();
                    foreach ($products as $product) {
                        $product->update(['discount_price' => null]);
                    }
                    //ilgili ürünlerin id lerini alıp,bu id'lerin variantlarını buluyoruz
                    $productIds = $products->pluck('id')->toArray();
                    $variants = ProductVariants::whereIn('product_id', $productIds)->get();
                    foreach ($variants as $variant) {
                        $variant->update(['discount_price' => null]);
                    }

                }
                break;
            case 'brands':
                foreach($discount->brands as $brand) {
                    $products = Products::whereHas('brands', function ($query) use ($brand) {
                        $query->where('categories.id', $brand->id);
                    })->get();
                    foreach ($products as $product) {
                        $product->update(['discount_price' => null]);
                    }
                    //ilgili ürünlerin id lerini alıp,bu id'lerin variantlarını buluyoruz
                    $productIds = $products->pluck('id')->toArray();
                    $variants = ProductVariants::whereIn('product_id', $productIds)->get();
                    foreach ($variants as $variant) {
                        $variant->update(['discount_price' => null]);
                    }

                }
            case 'variants':
                foreach($discount->variants as $variant){
                    $variant->update(['discount_price'=>null]);
                }
                break;
            default:
                break;
        }
        return response()->json([
            'success'=>true,
            'message'=>__('messages.discount_ended'),
        ],200);

    }
    public function destroy(Request $request,$id){
        $user=$request->user();
        if(!$user->hasPermissionTo('manage discounts')){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.unauthorized'),
            ],401);
        }
        $discount = Discounts::where('id', $id)->first();

        if(!$discount){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.discount_not_found'),
            ],404);
        }
        $discount->delete();
        return response()->json([
            'success'=> true,
            'message'=> __('messages.discount_deleted'),
        ],200);
    }
    //show discount
    public function show($id){
        $discount=Discounts::with(['categories','products','variants','brands'])->where('id',$id)->first();

        if(!$discount){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.discount_not_found'),
            ],404);
        }
        return response()->json([
            'success'=> true,
            'message'=> __('messages.discount_retrieved'),
            'data'=> $discount,
        ],200);
    }
}