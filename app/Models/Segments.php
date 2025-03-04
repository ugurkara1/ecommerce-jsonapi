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

    public  static function assignSegmentAutomatically(Customers $customer){

        $totalSpent=$customer->order()->sum('total_amount');
        if($totalSpent> 1000){
            return 'Gold';
        }
        else if($totalSpent>= 500){
            return 'Silver';
        }
        else{
            return 'Bronze';
        }

    }
}