<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Ownego\Cashier\Cashier;

return new class extends Migration
{
    public function up()
    {
        Schema::create((new Cashier::$subscriptionModel)->getTable(), function (Blueprint $table) {
            $table->id();
            $table->morphs('billable');
            $table->string('paypal_id')->unique();
            $table->string('paypal_product_id');
            $table->string('paypal_plan_id');
            $table->string('status');
            $table->unsignedInteger('quantity');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['billable_type', 'billable_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists((new Cashier::$subscriptionModel)->getTable());
    }
};
