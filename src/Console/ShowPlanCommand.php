<?php

namespace Ownego\Cashier\Console;

use Illuminate\Console\Command;
use Ownego\Cashier\Cashier;

class ShowPlanCommand extends Command
{
    protected $signature = 'cashier:show-plan {planId}';

    protected $description = 'Show plan details';

    public function handle()
    {
        $response = Cashier::api('get', 'billing/plans/'.$this->argument('planId'));

        $this->info(json_encode($response->json(), JSON_PRETTY_PRINT));
    }
}
