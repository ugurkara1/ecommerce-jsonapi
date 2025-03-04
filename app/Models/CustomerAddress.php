<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    //
    protected $table = "customer_adresses";
    protected $fillable = [
        'customer_id',
        'address_line',
        'city',
        'district',
        'postal_code',
    ];
    public function customer()
    {
        return $this->belongsTo(Customers::class,'customer_id');
    }
}