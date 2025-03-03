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
        Schema::table('product_qr_codes', function (Blueprint $table) {
            if (!Schema::hasColumn('product_qr_codes', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (!Schema::hasColumn('product_qr_codes', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('product_qr_codes', function (Blueprint $table) {
            $table->dropColumn(['created_at', 'updated_at']);
        });
    }

};