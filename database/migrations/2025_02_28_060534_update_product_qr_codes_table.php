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
        Schema::table('product_qr_codes', function (Blueprint $table) {
            //
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
            //Yeni sütün
            $table->foreignId('product_variant_id')->constrained('product_variants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_qr_codes', function (Blueprint $table) {
            //
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
            //Yeni sütün
            $table->foreignId('product_variant_id')->constrained('product_variants')->onDelete('cascade');
        });
    }
};