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
        Schema::table('product_images', function (Blueprint $table) {
            // Mevcut foreign key'i kaldır
            $table->dropForeign(['product_id']);
            // ON DELETE CASCADE ile yeniden oluştur
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');

            // Eğer variant_id için de aynı işlemi yapmak isterseniz
            $table->dropForeign(['variant_id']);
            $table->foreign('variant_id')
                ->references('id')
                ->on('product_variants')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            // Yeni foreign key'i kaldır
            $table->dropForeign(['product_id']);
            // Eski haliyle yeniden oluştur
            $table->foreign('product_id')
                ->references('id')
                ->on('products');

            // Eğer variant_id için de aynı işlemi yaptıysanız
            $table->dropForeign(['variant_id']);
            $table->foreign('variant_id')
                ->references('id')
                ->on('product_variants');
        });
    }
};