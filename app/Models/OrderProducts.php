<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderProducts extends Model
{
    //
    protected $table = "order_product";

    protected $fillable = [
        "order_id",
        "variant_id",
        "quantity",
        "quantity_type",
        "base_price",
        "sale_price",
        "tax_rate",
        "gift_package",
        "sku",
        "qr_code",
        'region_id'
    ];

    public function variant(){
        return $this->belongsTo(ProductVariants::class,'variant_id');
    }
    public function order(){
        return $this->belongsTo(Order::class,'order_id');
    }
    public function region(){
        return $this->belongsTo(Regions::class,'region_id');
    }
}