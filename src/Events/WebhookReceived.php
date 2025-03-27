<?php

namespace Ownego\Cashier\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebhookReceived
{
    use Dispatchable,
        SerializesModels;

    public function __construct(public array $payload)
    {
        //
    }
}
