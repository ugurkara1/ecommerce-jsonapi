<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaigns extends Model
{
    //
    protected $table = "campaigns";

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'target_audience',
        'is_active',
        'campaign_type',
    ];

    protected $date=['start_date','end_date'];

    public function order(){
        return $this->belongsToMany(Order::class,'order_campaigns','order_id','campaign_id');
    }
}