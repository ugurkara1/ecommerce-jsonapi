<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    //
    protected $table="orders";

    protected $fillable=[
        "customer_id",
        "full_name",
        "email",
        "phone_number",
        "payment_id",
        "campaign_id",
        "order_date",
        "order_status",
        "currency_code",
        "currency_rate",
        "subtotal",
        "tax_amount",
        "shipping_cost",
        "total_amount",
        "shipping_tracking_number",
        "shipment_date",
        "delivery_date",
    ];

    public function customer(){
        return $this->belongsTo(Customers::class,'customer_id');
    }

    public function payment(){
        return $this->hasOne(Payment::class,'payment_id');
    }

    public function campaigns(){
        return $this->belongsToMany(Campaigns::class,'order_campaigns','order_id','campaign_id');
    }
    public function addresses(){
        return $this->hasMany(OrderAddress::class,'order_id');
    }
    public function shippingAddress(){
        return $this->hasOne(OrderAddress::class,'order_id')->where('address_type', 'shipping');
    }

    public function billingAddress()
    {
        return $this->hasOne(OrderAddress::class)->where('address_type', 'billing');
    }

    public function orderProducts(){
        return $this->hasMany(OrderProducts::class,'order_id');
    }

    public function orders(){
        return $this->hasOne(Order::class,'order_id');
    }

}
