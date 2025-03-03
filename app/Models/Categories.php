<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categories extends Model
{
    //
    use HasFactory;
    protected $table='categories';
    protected $fillable = [
        'name',
        'parent_category_id',
        'slug',
    ];
    public function parent(){
        return $this->belongsTo(Categories::class,'parent_category_id');

    }
    public function children(){
        return $this->hasMany(Categories::class,'parent_category_id');
    }
    public function products(){
        return $this->belongsToMany(Products::class, 'product_categories', 'category_id', 'product_id');
    }
}