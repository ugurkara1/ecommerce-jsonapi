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
        Schema::table('orders', function (Blueprint $table) {
            //
            $table->string('current_status')->default('Sipariş Oluşturma')->after('delivery_date');
        });
    }

    /**
     * Reverse the migrations.
     */
/**
 * Reverse the migrations.
 */
public function down(): void
{
    Schema::table('orders', function (Blueprint $table) {
        $table->dropColumn('current_status');
    });
}

};