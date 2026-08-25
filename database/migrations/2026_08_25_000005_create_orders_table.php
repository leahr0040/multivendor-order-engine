<?php

use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->char('idempotency_key', 26)->nullable()->unique();
            $table->unsignedInteger('original_price');
            $table->unsignedInteger('discount');
            $table->unsignedInteger('final_price');
            $table->string('status')->default(OrderStatus::Pending->value);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
