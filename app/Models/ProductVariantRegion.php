<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantRegion extends Model
{
    //
    protected $table='product_variant_regions';

    protected $fillable=[
        'product_variant_id',
        'region_id',
        'price',
        'discount_price',
        'stock'
    ];
    public function variant()
    {
        return $this->belongsTo(ProductVariants::class);
    }

    public function region()
    {
        return $this->belongsTo(Regions::class);
    }
    // app/Models/ProductVariants.php
    public function regionPricing()
    {
        return $this->hasMany(ProductVariantRegion::class, 'product_variant_id');
    }

    //Fiyat ve Stock için accessorlar
    public function getPriceAttribute($value)
    {
        $regionId = request()->header('Region-Id') ?? config('app.default_region');

        if($regionPrice = $this->regionPricing->where('region_id', $regionId)->first()) {
            return $regionPrice->price;
        }

        return $value;


    }
    public function getStockAttribute($value)
    {
        $regionId = request()->header('Region-Id') ?? config('app.default_region');

        if($regionStock = $this->regionPricing->where('region_id', $regionId)->first()) {
            return $regionStock->stock;
        }

        return $value;
    }
}
