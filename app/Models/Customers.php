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
        return $this->hasOne(CustomerProfile::class);

    }
    public function addresses(){
        return $this->hasMany(CustomerAddress::class);
    }
}