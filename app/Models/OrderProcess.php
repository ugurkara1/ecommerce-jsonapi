<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderProcess extends Model
{
    //
    use HasFactory;
    protected $table = 'order_process';
    protected $fillable=[
        'name',
        'description'
    ];
    public function orders() {
        return $this->hasMany(Order::class,'process_id');
    }
}
