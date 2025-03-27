<?php

namespace Ownego\Cashier;

use Ownego\Cashier\Concerns\ManageSubscriptions;
use Ownego\Cashier\Concerns\PerformCharges;

trait Billable
{
    use PerformCharges;
    use ManageSubscriptions;
}
