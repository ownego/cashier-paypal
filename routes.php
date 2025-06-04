<?php

use Illuminate\Support\Facades\Route;
use Ownego\Cashier\Http\Controllers\WebhookController;

Route::post('paypal/webhook', [
    'uses' => WebhookController::class.'@handle',
    'as' => 'cashier.paypal.webhook',
])->middleware(['web', 'auth'])->name('cashier.paypal.webhook');
