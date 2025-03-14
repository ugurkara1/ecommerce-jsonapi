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
        Schema::table('order_processes', function (Blueprint $table) {
            //
            $table->dropColumn('status');
        });

        Schema::table('order_processes', function (Blueprint $table) {
            //
            $table->enum('status',[
                'Sipariş Oluşturma',
                'Ödeme İşlemi',
                'Sipariş Onayı',
                'Hazırlık',
                'Kargo ve Teslimat',
                'Teslimat ve Onay',
                'İade ve İptal Süreci'
            ])->after('order_id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_processes', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('order_processes', function (Blueprint $table) {
            $table->enum('status', [
                'Sipariş Oluşturme',
                'Ödeme İşlemi',
                'Sipariş Onayı',
                'Hazırlık',
                'Kargo ve Teslimat',
                'Teslimat ve Onay',
                'İade ve İptal Süreçler'
            ])->after('order_id');
        });
    }
};
