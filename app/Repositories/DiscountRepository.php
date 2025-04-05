<?php

namespace App\Repositories;
use App\Contracts\DiscountContract;
use App\Models\Discount;
use App\Models\Discounts;
use App\Models\Products;
use App\Models\ProductVariants;

class DiscountRepository implements DiscountContract{
    public function getAll(){
        return Discounts::with(['categories','products','variants','brands'])->get();
    }
    public function show($id){
        return Discounts::with(['categories','products','variants','brands'])->findorFail($id);
    }
    public function create(array $data, $user){
        $discount=Discounts::create([
            'name'=>$data['name'] ,
            'discount_type'=>$data['discount_type'] ?? null,
            'value'=>$data['value'] ?? null,
            'start_date'=>$data['start_date'] ?? null,
            'end_date'=>$data['end_date'] ?? null,
            'applies_to'=>$data['applies_to'] ?? 'all',
            'is_active'=>$data['is_active'] ?? true,
        ]);
        // Pivot ilişkiler ve fiyat güncellemeleri
        // İndirimin uygulanacağı seviyeye göre ilişkileri senkronize et ve fiyatları güncelle
        if ($data['applies_to'] === 'categories' && isset($data['category_ids'])) {
            $discount->categories()->sync($data['category_ids']);
            $this->updateDiscountPricesByCategories($data['category_ids'], $discount);
        }
        else if ($data['applies_to'] === 'products' && isset($data['product_ids'])){
            $discount->products()->sync($data['product_ids']);
            $this->updateDiscountPricesByProducts($data['product_ids'], $discount);
        }
        else if ($data['applies_to'] === 'variants' && isset($data['variant_ids'])){
            $discount->variants()->sync($data['variant_ids']);
            $this->updateDiscountPricesByVariants($data['variant_ids'], $discount);
        }
        else if ($data['applies_to'] === 'brands' && isset($data['brand_ids'])){
            $discount->brands()->sync($data['brand_ids']);
            $this->updateDiscountPricesByBrands($data['brand_ids'], $discount);
        }
        else if ($data['applies_to'] === 'segments' && isset($data['segment_ids'])){
            $discount->segments()->sync($data['segment_ids']);
            // $this->updateDiscountPricesBySegments($discount);
        }
        activity()
            ->causedBy($user)
            ->performedOn($discount)
            ->withProperties(['discount' => $data])
            ->log('Discount created successfully');

        return $discount;
    }



    private function updateDiscountPricesByCategories($categoryIds, $discount){
        $products = Products::whereHas('categories', function ($query) use ($categoryIds) {
            $query->whereIn('categories.id', $categoryIds);
        })->get();

        foreach ($products as $product){
            $this->applyDiscount($product, $discount);
        }

        $productIds = $products->pluck('id')->toArray();
        $variants = ProductVariants::whereIn('product_id', $productIds)->get();
        foreach ($variants as $variant) {
            $this->applyDiscountToVariant($variant, $discount);
        }
    }

    private function updateDiscountPricesByProducts($productIds, $discount){
        $products = Products::whereIn('id', $productIds)->get();
        foreach ($products as $product){
            $this->applyDiscount($product, $discount);
        }
        $productIds = $products->pluck('id')->toArray();
        $variants = ProductVariants::whereIn('product_id', $productIds)->get();
        foreach ($variants as $variant){
            $this->applyDiscountToVariant($variant, $discount);
        }
    }

    private function updateDiscountPricesByVariants($variantIds, $discount){
        $variants = ProductVariants::whereIn('id', $variantIds)->get();
        foreach ($variants as $variant){
            $this->applyDiscount($variant, $discount);
        }
    }

    private function updateDiscountPricesByBrands($brandIds, $discount){
        $products = Products::whereHas('brands', function ($query) use ($brandIds): void {
            $query->whereIn('brands.id', $brandIds);
        })->get();

        foreach ($products as $product){
            $this->applyDiscount($product, $discount);
        }

        $productIds = $products->pluck('id')->toArray();
        $variants = ProductVariants::whereIn('product_id', $productIds)->get();
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
                'success' => false,
                'message' => __('messages.no_variant_found'),
            ], 404);
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
    public function update(array $data, $id, $user)
    {
        $discount = Discounts::where('id',$id)->first();
        if (!$discount) {
            return null;
        }

        $discount->update([
            'name'          => $data['name'] ?? $discount->name,
            'discount_type' => $data['discount_type'] ?? $discount->discount_type,
            'value'         => $data['value'] ?? $discount->value,
            'start_date'    => $data['start_date'] ?? $discount->start_date,
            'end_date'      => $data['end_date'] ?? $discount->end_date,
            'applies_to'    => $data['applies_to'] ?? $discount->applies_to,
            'is_active'     => $data['is_active'] ?? $discount->is_active,
        ]);

        // Pivot ilişkileri güncelleme
        if (isset($data['applies_to'])) {
            if ($data['applies_to'] === 'categories' && isset($data['category_ids'])) {
                $discount->categories()->sync($data['category_ids']);
                $this->updateDiscountPricesByCategories($data['category_ids'], $discount);
            } elseif ($data['applies_to'] === 'products' && isset($data['product_ids'])) {
                $discount->products()->sync($data['product_ids']);
                $this->updateDiscountPricesByProducts($data['product_ids'], $discount);
            } elseif ($data['applies_to'] === 'variants' && isset($data['variant_ids'])) {
                $discount->variants()->sync($data['variant_ids']);
                $this->updateDiscountPricesByVariants($data['variant_ids'], $discount);
            } elseif ($data['applies_to'] === 'segments' && isset($data['segment_ids'])) {
                $discount->segments()->sync($data['segment_ids']);
            }
        }

        activity()
            ->causedBy($user)
            ->performedOn($discount)
            ->withProperties(['discount' => $data])
            ->log('Discount updated successfully');

        return $discount->load(['categories', 'products', 'variants', 'brands']);
    }
    public function delete($id, $user)
    {
        $discount = Discounts::where('id', $id)->first();

        if (!$discount) {
            return null;
        }

        // Önce pivot tablolardaki ilişkileri kaldır
        $discount->variants()->detach();
        $discount->products()->detach();
        $discount->categories()->detach();
        $discount->brands()->detach();
        $discount->segments()->detach();

        // Daha sonra indirimi sil
        $discount->delete();

        activity()
            ->causedBy($user)
            ->performedOn($discount)
            ->log('Discount deleted successfully');

        return $discount;
    }
    public function endDiscount($discountId, $user)
    {
        $discount = Discounts::with(['products', 'categories', 'variants', 'brands'])->where('id',$discountId)->first();
        if (!$discount) {
            return null;
        }

        $discount->update(['is_active' => false]);

        // Uygulama düzeyine göre indirim fiyatlarını sıfırlama
        switch ($discount->applies_to) {
            case 'products':
                foreach ($discount->products as $product) {
                    $product->update(['discount_price' => null]);
                }
                break;
            case 'categories':
                foreach ($discount->categories as $category) {
                    $products = Products::whereHas('categories', function ($query) use ($category) {
                        $query->where('categories.id', $category->id);
                    })->get();
                    foreach ($products as $product) {
                        $product->update(['discount_price' => null]);
                    }
                    $productIds = $products->pluck('id')->toArray();
                    $variants = ProductVariants::whereIn('product_id', $productIds)->get();
                    foreach ($variants as $variant) {
                        $variant->update(['discount_price' => null]);
                    }
                }
                break;
            case 'brands':
                foreach ($discount->brands as $brand) {
                    $products = Products::whereHas('brands', function ($query) use ($brand) {
                        $query->where('brands.id', $brand->id);
                    })->get();
                    foreach ($products as $product) {
                        $product->update(['discount_price' => null]);
                    }
                    $productIds = $products->pluck('id')->toArray();
                    $variants = ProductVariants::whereIn('product_id', $productIds)->get();
                    foreach ($variants as $variant) {
                        $variant->update(['discount_price' => null]);
                    }
                }
                break;
            case 'variants':
                foreach ($discount->variants as $variant) {
                    $variant->update(['discount_price' => null]);
                }
                break;
            default:
                break;
        }

        activity()
            ->causedBy($user)
            ->performedOn($discount)
            ->withProperties(['discount' => $discount])
            ->log('Discount ended successfully');

        return $discount;
    }

}