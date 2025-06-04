<?php

namespace Ownego\Cashier\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Ownego\Cashier\Cashier;

trait ManageSale
{
    public function sales(): MorphMany
    {
        return $this->morphMany(Cashier::$saleModel, 'billable')->orderByDesc('created_at');
    }
}
