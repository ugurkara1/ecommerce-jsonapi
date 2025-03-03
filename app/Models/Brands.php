<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Products;

class Brands extends Model
{
    //
    use HasFactory;

    protected $table='brands';
    protected $fillable=[
        'name','logo_url'
    ];
    public function products(){
        return $this->hasMany(Products::class,'brand_id');
    }
}