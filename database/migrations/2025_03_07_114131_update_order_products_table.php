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
        Schema::table('order_products', function (Blueprint $table) {
            // Önce foreign key kısıtlamasını kaldır
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');

            // Yeni variant_id sütununu ekle ve foreign key tanımla
            $table->foreignId('variant_id')->after('order_id')->constrained('product_variants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            // variant_id'yi kaldır
            $table->dropForeign(['variant_id']);
            $table->dropColumn('variant_id');

            // Eski product_id sütununu geri ekle
            $table->foreignId('product_id')->after('order_id')->constrained('products')->onDelete('cascade');
        });
    }
};