<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Regions;
use App\Models\Country;

class RegionSeeder extends Seeder
{
    public function run()
    {
        // Ülkelere ait örnek bölgeleri ekleyelim

        // Türkiye için bölgeler
        $turkey = Country::where('code', 'TR')->first();
        Regions::create([
            'name'       => 'Marmara',
            'code'       => 'MAR',
            'currency'   => 'TRY',
            'is_active'  => true,
            'country_id' => $turkey->id,
        ]);
        Regions::create([
            'name'       => 'Ege',
            'code'       => 'EGE',
            'currency'   => 'TRY',
            'is_active'  => true,
            'country_id' => $turkey->id,
        ]);

        // ABD için bölgeler
        $us = Country::where('code', 'US')->first();
        Regions::create([
            'name'       => 'West',
            'code'       => 'WST',
            'currency'   => 'USD',
            'is_active'  => true,
            'country_id' => $us->id,
        ]);
        Regions::create([
            'name'       => 'East',
            'code'       => 'EST',
            'currency'   => 'USD',
            'is_active'  => true,
            'country_id' => $us->id,
        ]);

        // Almanya için bölgeler
        $germany = Country::where('code', 'DE')->first();
        Regions::create([
            'name'       => 'Bavaria',
            'code'       => 'BAV',
            'currency'   => 'EUR',
            'is_active'  => true,
            'country_id' => $germany->id,
        ]);
        Regions::create([
            'name'       => 'Berlin',
            'code'       => 'BER',
            'currency'   => 'EUR',
            'is_active'  => true,
            'country_id' => $germany->id,
        ]);
    }
}