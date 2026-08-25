<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sub_order_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('vendor_product_id')->nullable()->index();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('original_unit_price');
            $table->unsignedInteger('original_price');
            $table->unsignedInteger('discount');
            $table->unsignedInteger('final_price');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
