<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderAddress extends Model
{
    //
    protected $table = "order_addresses";

    protected $fillable = [
        "order_id",
        "address_type",
        "company_name",
        "recipient_name",
        "street",
        "city",
        "district",
        "postal_code",
        "country",
        "phone",
    ];

    public function orders(){
        return $this->belongsTo(Order::class,'order_id');
    }



}
