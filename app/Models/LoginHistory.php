<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginHistory extends Model
{
    use HasFactory;
    //
    protected $fillable=[
        'customer_id',
        'ip_address',
        'device',
        'platform',
        'browser',
        'login_at',
    ];

    public function customers(){
        return $this->belongsTo(Customers::class,'customer_id');
    }
}
