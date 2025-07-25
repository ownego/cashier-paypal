<?php

namespace Ownego\Cashier;

use Ownego\Cashier\Concerns\ManageCustomer;
use Ownego\Cashier\Concerns\ManageSale;
use Ownego\Cashier\Concerns\ManageSubscriptions;
use Ownego\Cashier\Concerns\PerformCharges;

trait Billable
{
    use ManageCustomer;
    use ManageSubscriptions;
    use ManageSale;
    use PerformCharges;
}
