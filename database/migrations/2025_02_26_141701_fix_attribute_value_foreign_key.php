<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('product_variant_attributes', function (Blueprint $table) {
            // Eski yabancı anahtarı sil
            $table->dropForeign(['attribute_value_id']);

            // Yeni doğru yabancı anahtarı ekle
            $table->foreign('attribute_value_id')
                ->references('id')
                ->on('attribute_values')
                ->onDelete('cascade');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};