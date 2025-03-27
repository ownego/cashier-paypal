<?php

namespace Ownego\Cashier\Console;

use Illuminate\Console\Command;
use Ownego\Cashier\Cashier;

class ListPlanCommand extends Command
{
    protected $signature = 'cashier:list-plan {--product-id=}';

    protected $description = 'Listing paypal product\'s plans';

    public function handle()
    {
        $query = [];

        if ($this->option('product-id')) {
            $query['product_id'] = $this->option('product-id');
        }

        $plans = Cashier::api('get', 'billing/plans', $query)['plans'];

        $this->table(
            ['Product Id', 'Plan Id', 'Name', 'Description', 'Status', 'Create Time'],
            array_map(function ($plan) {
                return [
                    $plan['product_id'],
                    $plan['id'],
                    $plan['name'],
                    $plan['description'] ?? '',
                    $plan['status'],
                    $plan['create_time'],
                ];
            }, $plans)
        );
    }
}
