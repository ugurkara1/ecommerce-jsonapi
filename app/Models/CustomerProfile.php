<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerProfile extends Model
{
    //
    protected $table = "customer_profiles";
    protected $fillable = [
        'customer_id',
        'nameSurname',  
        'phone',
        'weight',
        'height',
        'birthday',
        'gender',
    ];
    public function customer(){
        return $this->belongsTo(Customers::class,'customer_id');
    }
}