<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class orderProcesses extends Model
{
    //
    protected $table="order_processes";

    protected $fillable=[
        'order_id',
        'status',
        'description'
    ];
    public function order(){
        return $this->belongsTo(Order::class,'order_id');
    }
}