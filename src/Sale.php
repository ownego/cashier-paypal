<?php

namespace Ownego\Cashier;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'paypal_id',
        'paypal_subscription_id',
        'amount',
        'currency',
        'status',
    ];
}
