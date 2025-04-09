<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Ownego\Cashier\Cashier;

return new class extends Migration
{
    public function up()
    {
        Schema::create((new Cashier::$customerModel)->getTable(), function (Blueprint $table) {
            $table->id();
            $table->morphs('billable');
            $table->string('name')->nullable();
            $table->string('email');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();

            $table->index(['billable_type', 'billable_id', 'email']);
        });
    }
};
