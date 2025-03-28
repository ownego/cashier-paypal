<?php

use Illuminate\Support\Facades\Route;
use Ownego\Cashier\Http\Controllers\WebhookController;

Route::post('webhook', WebhookController::class)->name('webhook');
