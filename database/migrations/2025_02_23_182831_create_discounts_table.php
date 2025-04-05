<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiscountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id(); // Otomatik artan id
            $table->string('name'); // İndirim adı, NOT NULL varsayılanı ile oluşturulur
            $table->enum('discount_type', ['percentage', 'fixed']); // İndirim türü
            $table->decimal('value', 10, 2); // İndirim değeri
            $table->timestamp('start_date')->nullable(); // Başlangıç tarihi
            $table->timestamp('end_date')->nullable(); // Bitiş tarihi
            $table->enum('applies_to', ['all', 'categories', 'products', 'variants', 'segments']); // Uygulama alanı
            $table->boolean('is_active')->default(true); // Aktiflik durumu
            $table->timestamps(); // created_at ve updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
}