<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discounts extends Model
{
    //
    use HasFactory;
    protected $table = 'discounts';
    protected $fillable = [
        'name',
        'discount_type',
        'value',
        'start_date',
        'end_date',
        'applies_to',
        'is_active'
    ];
    protected $dates=['start_date','end_date'];
    public function categories(){
        return $this->belongsToMany(Categories::class,'discount_categories','discount_id','category_id');
    }
    public function products(){
        return $this->belongsToMany(Products::class,'discount_products','discount_id','product_id');
    }
    public function variants()
    {
        return $this->belongsToMany(ProductVariants::class, 'discount_variants','discount_id','variant_id');
    }
    public function segments(){
        return $this->belongsToMany(Segments::class,'discount_segments','discount_id','segment_id');
    }
    public function brands(){
        return $this->belongsToMany(Brands::class,'discount_brands','discount_id','brand_id');
    }
}