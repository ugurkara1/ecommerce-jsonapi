<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProcessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('order_process')->insert([
            ['name' => 'Order Creation', 'description' => 'Order create', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Payment Process', 'description' => 'Payment will be made', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Order Confirm', 'description' => 'Order confirmed', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Order Preparing', 'description' => 'Order Processing', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Cargo', 'description' => 'Your order is in cargo', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Delivery', 'description' => 'Your order has been delivered', 'created_at' => now(), 'updated_at' => now()],
        ]);

    }
}