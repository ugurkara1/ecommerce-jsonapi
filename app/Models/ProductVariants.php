<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\AttributeValues;

class ProductVariants extends Model
{
    //
    use HasFactory;
    protected $table = "product_variants";
    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'discount_price',
        'stock',
    ];
    public function product(){
        return $this->belongsTo(Products::class,'product_id');
    }
    public function attributeValue(){
        return $this->belongsToMany(AttributeValues::class,'product_variant_attributes','variant_id','attribute_value_id');
    }
    public function getFinalPriceAttribute()
    {
        return $this->price ?? $this->product->price;
    }
    public function qrCodes(){
        return $this->hasMany(ProductQrCodes::class,'product_variant_id');
    }
    public function images(){
        return $this->hasMany(ProductImages::class,'variant_id');
    }
    // app/Models/ProductVariants.php

    public function regionPricing() {
        return $this->hasMany(ProductVariantRegion::class, 'product_variant_id');
    }

}
