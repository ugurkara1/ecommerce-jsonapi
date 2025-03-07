<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttributeValues extends Model
{
    //
    use HasFactory;

    protected $table='attribute_values';
    protected $fillable=[
        'attribute_id',
        'value',
    ];
    public function attribute(){
        return $this->belongsTo(Attributes::class,'attribute_id');
    }
    public function variants(){
        return $this->belongsToMany(ProductVariants::class,'product_variant_attributes','attribute_value_id','variant_id');
    }
}
