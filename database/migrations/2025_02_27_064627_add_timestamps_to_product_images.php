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
        Schema::table('product_images', function (Blueprint $table) {
            if (!Schema::hasColumn('product_images', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (!Schema::hasColumn('product_images', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });

    }

    public function down()
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->dropTimestamps();
        });
    }

};