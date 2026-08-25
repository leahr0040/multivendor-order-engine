<?php

use App\Enums\SubOrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_orders', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            $table->unsignedBigInteger('order_id')->index();
            $table->unsignedBigInteger('vendor_id')->index();
            $table->unsignedInteger('original_price');
            $table->unsignedInteger('discount');
            $table->unsignedInteger('final_price');
            $table->string('status')->default(SubOrderStatus::Pending->value);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_orders');
    }
};
