<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Customers extends Model
{
    //
    use HasFactory,Notifiable,HasApiTokens;
    protected $fillable = [
        'email',
        'password',
    ];
    protected $hidden= [
        "password",
        'remember_token',
    ];
    public function profile(){
        return $this->hasOne(CustomerProfile::class,'customer_id');

    }
    public function addresses(){
        return $this->hasMany(CustomerAddress::class,'customer_id');
    }

    public function segments(){
        return $this->belongsToMany(Segments::class,'customer_segment','customer_id','segment_id');
    }

    public function loginHistories(){
        return $this->hasMany(LoginHistory::class,'customer_id');
    }

    //otomatk segment atama
    public function assignSegment(){
        $segmentName = Segments::assignSegmentAutomatically($this);
        $segment=Segments::firstOrCreate(['name'=> $segmentName]);

        //customers a adding
        $this->segments()->syncWithoutDetaching($segment->id);

    }


    //indirimli fiyat
    public function getDiscountPrice($price){
        $discount=0;
        foreach ($this->segments as $segment) {
            // Segmentin indirim oranını alıyoruz
            $discount = max($discount, $segment->getDiscountPercentage());
        }

        // İndirimli fiyatı hesaplıyoruz
        return $price * (1 - ($discount / 100));
    }
}