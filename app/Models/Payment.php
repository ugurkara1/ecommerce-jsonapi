<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    //
    protected $table="payments";

    protected $fillable=[
        'customer_id',
        'payment_method',
        'amount',
        'payment_status',
        'payment_date',
    ];

    protected $dates=['payment_date'];

    public function customer(){
        return $this->belongsTo(Customers::class,'customer_id');
    }
    public function order(){
        return $this->belongsTo(Order::class,'payment_id');
    }
}