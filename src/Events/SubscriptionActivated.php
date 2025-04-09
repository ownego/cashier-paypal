<?php

namespace Ownego\Cashier\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Ownego\Cashier\Subscription;

class SubscriptionActivated
{
    use Dispatchable,
        SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public array $payload
    )
    {
        //
    }
}
