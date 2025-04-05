<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Log;

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
    public function payment(){
        return $this->hasMany(Payment::class,'customer_id');
    }
    public function order(){
        return $this->hasMany(Order::class,'customer_id');
    }

    public function invoices(){
        return $this->hasMany(Invoices::class,'customer_id');
    }


    public function assignSpendingSegments(){
        $total = $this->order()->sum('total_amount');
        Log::info("Toplam harcama: " . $total);

        $tiers = [
            'VIP' => 1000000,
            'Gold' => 500000,
            'Silver' => 200000,
            'Bronze' => 0,
        ];

        $selectedSegmentName = 'Bronze';

        foreach ($tiers as $name => $minAmount) {
            if ($total >= $minAmount) {
                $selectedSegmentName = $name;
                break;
            }
        }

        Log::info("Seçilen segment: " . $selectedSegmentName);

        // Müşterinin eski segmentlerini kaldır
        $spendingSegmentIds = Segments::whereIn('name', array_keys($tiers))->pluck('id');
        Log::info("Kaldırılacak segment ID'leri: " . json_encode($spendingSegmentIds));
        $this->segments()->detach($spendingSegmentIds);

        // Yeni segment ekle
        if ($selectedSegment = Segments::where('name', $selectedSegmentName)->first()) {
            Log::info("Yeni segment ID: " . $selectedSegment->id);
            $this->segments()->attach($selectedSegment->id);
        } else {
            Log::info("Segment bulunamadı: " . $selectedSegmentName);
        }
    }
    public function assignSegment()
    {
        // Örneğin, basit bir mantık: toplam harcama kontrolü gibi
        $this->assignSpendingSegments(); // veya kendi mantığınızı ekleyin
    }


    public function getDiscountedPriceForProduct($product)
    {
        $basePrice = $product->price;

        // Müşterinin varsayılan segmentini alıyoruz (her müşterinin bir segmenti olduğunu varsayıyoruz)
        $segment = $this->segments()->first();
        if (!$segment) {
            return $basePrice;
        }

        // Aktif, segment bazlı indirimi sorguluyoruz
        $discount = \App\Models\Discounts::where('is_active', true)
                    ->where('applies_to', 'segments')
                    ->whereHas('segments', function($query) use ($segment) {
                        $query->where('id', $segment->id);
                    })->orderByDesc('value')->first();

        if (!$discount) {
            return $basePrice;
        }

        // İndirim tipine göre nihai fiyat hesaplaması
        if ($discount->discount_type === 'percentage') {
             return round($basePrice * (1 - $discount->value / 100), 2);
        } else { // 'fixed' tipindeki indirim için
            return max(round($basePrice - $discount->value, 2), 0);
        }
    }

}
