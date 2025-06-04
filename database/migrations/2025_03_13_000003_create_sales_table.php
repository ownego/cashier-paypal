<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Ownego\Cashier\Cashier;

return new class extends Migration
{
    public function up()
    {
        Schema::create((new Cashier::$saleModel)->getTable(), function (Blueprint $table) {
            $table->id();
            $table->morphs('billable');
            $table->string('paypal_id')->unique();
            $table->string('paypal_subscription_id');
            $table->float('amount');
            $table->string('currency')->default('USD');
            $table->string('status')->default('completed');
            $table->timestamps();

            $table->index(['billable_type', 'billable_id', 'paypal_id']);
            $table->index(['billable_type', 'billable_id', 'paypal_subscription_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists((new Cashier::$saleModel)->getTable());
    }
};
