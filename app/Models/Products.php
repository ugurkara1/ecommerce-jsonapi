<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProductVariants;
use App\Models\ProductImages;
use App\Models\ProductQrCodes;

class Products extends Model
{
    //
    use HasFactory;
    protected $table='products';
    protected $fillable=[
        'sku',
        'name',
        'description',
        'price',
        'discount_price',
        'brand_id',
        'is_active',
        'slug',
    ];
    public function brands(){
        return $this->belongsTo(Brands::class,'brand_id');
    }
    public function categories(){
        return $this->belongsToMany(Categories::class,'product_categories',"product_id","category_id");

    }
    public function variants(){
        return $this->hasMany(ProductVariants::class,'product_id');
    }
    public function productImage(){
        return $this->hasMany(ProductImages::class,'product_id');
    }
    /*public function qrCodes(){
        return $this->hasMany(ProductQrCodes::class,'product_id');
    }*/
    public function orderProducts(){
        return $this->hasMany(OrderProducts::class,'product_id');
    }
}