<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImages extends Model
{
    //
    use HasFactory;
    protected $table='product_images';
    protected $fillable=[
        'product_id',
        'variant_id',
        'image_url',
        'is_main',
        'sort_order',
    ];
    protected $appends=['full_image_url'];
    public function getFullImageUrlAttribute(){
        return asset('storage/'.$this->image_url);
    }
    public function product(){
        return $this->belongsTo(Products::class,'product_id');
    }
    public function variants(){
        return $this->hasMany(ProductVariants::class,'product_id');
    }

}