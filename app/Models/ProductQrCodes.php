<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductQrCodes extends Model
{
    //
    use HasFactory;
    protected $table = 'product_qr_codes';
    protected $fillable = [
        'product_variant_id',
        'qr_data',
        'qr_image_url',
    ];

    public function productVariant()
    {
        return $this->belongsTo(ProductVariants::class, 'product_variant_id',);
    }
}