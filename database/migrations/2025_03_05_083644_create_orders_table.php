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
        Schema::create('orders', function (Blueprint $table) {
            $table->id(); // order_id
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade'); // Müşteri+            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null'); // Ödeme
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null'); // Ödeme
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->onDelete('set null'); // Kampanya
            $table->timestamp('order_date')->nullable(); // Sipariş tarihi
            $table->enum('order_status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])->default('pending'); // Sipariş durumu
            $table->enum('currency_code', ['USD', 'EUR', 'GBP', 'TRY', 'JPY', 'AUD', 'CAD'])->default('TRY'); // Para birimi
            $table->decimal('currency_rate', 10, 4)->default(1.0000); // Döviz kuru
            $table->decimal('subtotal', 10, 2); // Vergi ve kargo hariç toplam tutar
            $table->decimal('tax_amount', 10, 2); // Vergi miktarı
            $table->decimal('shipping_cost', 10, 2); // Kargo ücreti
            $table->decimal('total_amount', 10, 2); // Genel toplam (subtotal + tax + shipping)
            $table->string('shipping_tracking_number')->nullable(); // Kargo takip numarası
            $table->timestamp('shipment_date')->nullable(); // Kargo çıkış tarihi
            $table->timestamp('delivery_date')->nullable(); // Teslimat tarihi
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
