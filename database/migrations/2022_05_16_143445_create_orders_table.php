<?php

use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('external_order_id')->unique()->nullable();
            $table->foreignId('merchant_id')->constrained();
            $table->foreignId('affiliate_id')->nullable()->constrained();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            // As in affiliates table we're using decimal to store subtotal and commission_owed
            // So, we're using decimal here as well to avoid monetary values to avoid rounding errors with floating aithmetic
            $table->decimal('subtotal', 10, 3);
            $table->decimal('commission_owed', 10, 3)->default(0.000);
            $table->string('payout_status')->default(Order::STATUS_UNPAID);
            $table->string('discount_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
