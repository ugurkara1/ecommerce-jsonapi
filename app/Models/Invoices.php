<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoices extends Model
{
    //
    protected $table = "invoices";

    protected $fillable = [
        "order_id",
        "customer_id",
        "invoice_number",
        "invoice_date",
        "billing_address",
        "tax_amount",
        "total_amount",
        "currency",
    ];

    public function order(){
        return $this->belongsTo(Order::class,'order_id');
    }
    public function customer(){
        return $this->belongsTo(Customers::class,'customer_id');
    }
}
