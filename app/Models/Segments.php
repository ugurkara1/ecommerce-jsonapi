<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Segments extends Model
{
    //
    protected $table = "segments";
    protected $fillable = [
        "name",
    ];
    public function customers(){
        return $this->belongsToMany(Customers::class,'customer_segment','segment_id','customer_id');
    }



}
