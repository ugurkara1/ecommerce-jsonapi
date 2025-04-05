<?php
namespace App\Repositories;

use App\Contracts\ProductContract;
use App\Models\Products;

class ProductRepository implements ProductContract{
    public function getAll()
    {
        return Products::with('brands','categories','variants','variants.attributeValue','variants.qrCodes','productImage')->get();
    }
    public function show($id)
    {
        return Products::with('brands','categories','variants','variants.attributeValue','variants.qrCodes','productImage')->findOrFail($id);
    }
    public function create(array $data, $user)
    {
        // category_ids bilgisini ayrı alalım ve üründen çıkaralım
        $categoryIds = $data['category_ids'] ?? [];
        unset($data['category_ids']);

        $product = Products::create($data);

        // İlişkilendirmeyi valid veriden gelen category_ids üzerinden yapalım
        if (!empty($categoryIds)) {
            $product->categories()->sync($categoryIds);
        }

        activity()
            ->performedOn($product)
            ->causedBy($user)
            ->withProperties($data)
            ->log('Product created');

        return $product;
    }
    public function update($id, array $data, $user){
        // category_ids bilgisini ayrı alalım ve üründen çıkaralım
        $categoryIds = $data['category_ids'] ?? [];
        unset($data['category_ids']);

        $product = Products::where('id',$id)->first();
        $product->update($data);

        // İlişkilendirmeyi valid veriden gelen category_ids üzerinden yapalım
        if (!empty($categoryIds)) {
            $product->categories()->sync($categoryIds);
        }

        activity()
            ->performedOn($product)
            ->causedBy($user)
            ->withProperties($data)
            ->log('Product updated');

        return $product;
    }

    public function delete($id,$user){
        $product = Products::where('id',$id)->first();
        $product->delete();
        activity()
            ->performedOn($product)
            ->causedBy($user)
            ->log('Product deleted');
        return $product;
    }

}