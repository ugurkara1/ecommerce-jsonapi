<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    //
    protected $table='countries';
    protected $fillable=[
        'name','code'
    ];

    public function regions(){
        return $this->hasMany(Regions::class,'country_id');
    }
}
