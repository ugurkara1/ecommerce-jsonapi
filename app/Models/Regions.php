<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Regions extends Model
{
    //
    protected $table='regions';
    protected $fillable=[
        'name','code','currency','is_active','country_id'
    ];

    public function country(){
        return $this->belongsTo(Country::class,'country_id');
    }
    public function orderProduct(){
        return $this->hasMany(OrderProducts::class,'region_id');
    }
}