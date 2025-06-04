<?php

namespace Ownego\Cashier\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Ownego\Cashier\Sale;

class SaleReversed
{
    use Dispatchable,
        SerializesModels;

    public function __construct(
        public Sale $sale,
        public array $payload
    )
    {
        //
    }
}
