<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    //
    protected $table="orders";

    protected $fillable=[
        "order_number",
        "customer_id",
        "process_id",
        "full_name",
        "email",
        "phone_number",
        "payment_id",
        "campaign_id",
        "order_date",
        "currency_code",
        "currency_rate",
        "subtotal",
        "tax_amount",
        "shipping_cost",
        "total_amount",
        "shipping_tracking_number",
        "shipment_date",
        "delivery_date",
        "return_code",
        "current_status"
    ];
    public function process()
    {
        return $this->belongsTo(OrderProcess::class, 'process_id');
    }
    public function customer(){
        return $this->belongsTo(Customers::class,'customer_id');
    }

    public function payment(){
        return $this->belongsTo(Payment::class,'payment_id');
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
    public function orderProcesses(){
        return $this->hasMany(orderProcesses::class,'order_id');
    }

    // Geçerli durum geçiş kuralları
    public static $statusTransitions = [
        'Order Creation' => ['Payment Process'],
        'Payment Process' => ['Order Confirm'],
        'Order Confirm' => ['Order Preparing'],
        'Order Preparing' => ['Cargo'],
        'Cargo' => ['Delivery'],
        //'Delivery' => ['İade ve İptal Süreçler']
    ];

}