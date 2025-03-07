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
        Schema::table('customer_adresses', function (Blueprint $table) {
            $table->enum('address_type', ['shipping', 'billing'])->after('customer_id');
            $table->string('company_name')->nullable()->after('address_type');
            $table->string('recipient_name')->after('company_name');
            $table->string('street')->after('recipient_name');
            $table->string('country')->after('postal_code');
            $table->string('phone')->nullable()->after('country');

            // Eskiden "address_line" vardı, yerine "street" eklediğimiz için onu kaldırabiliriz
            $table->dropColumn('address_line');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_adresses', function (Blueprint $table) {
            $table->dropColumn(['address_type', 'company_name', 'recipient_name', 'street', 'country', 'phone']);
            $table->string('address_line')->after('customer_id'); // Geri almak için
        });
    }
};