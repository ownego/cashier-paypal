<?php

use Illuminate\Support\Facades\Route;
use Ownego\Cashier\Http\Controllers\WebhookController;
use Ownego\Cashier\Http\Middleware\VerifyWebhookSignature;

Route::post('webhook', WebhookController::class)->name('webhook');
