<?php

namespace Ownego\Cashier\Console;

use Illuminate\Console\Command;
use Ownego\Cashier\Cashier;

class ListProductCommand extends Command
{
    protected $signature = 'cashier:list-product';

    protected $description = 'Listing paypal products';

    public function handle()
    {
        $products = Cashier::api('get', 'catalogs/products')['products'];

        $this->table(
            ['ID', 'Name', 'Description', 'Create Time'],
            array_map(function ($product) {
                return [
                    $product['id'],
                    $product['name'],
                    $product['description'] ?? '',
                    $product['create_time'],
                ];
            }, $products)
        );
    }
}
