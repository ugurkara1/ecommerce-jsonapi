<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_variant_regions', function (Blueprint $table) {
            //
            $table->decimal('price_percentage',5,2)->nullable()->after('discount_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variant_regions', function (Blueprint $table) {
            //
            $table->dropColumn('price_percentage');
        });
    }
};