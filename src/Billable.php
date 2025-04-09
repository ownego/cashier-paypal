<?php

namespace Ownego\Cashier;

use Ownego\Cashier\Concerns\ManageCustomer;
use Ownego\Cashier\Concerns\ManageSubscriptions;
use Ownego\Cashier\Concerns\PerformCharges;

trait Billable
{
    use ManageCustomer;
    use ManageSubscriptions;
    use PerformCharges;
}
