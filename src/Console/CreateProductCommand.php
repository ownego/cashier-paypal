<?php

namespace Ownego\Cashier\Console;

use Illuminate\Console\Command;
use Ownego\Cashier\Cashier;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class CreateProductCommand extends Command
{
    protected $signature = 'cashier:create-product';

    protected $description = 'Create paypal product for cashier';

    public function handle()
    {
        $name = text(
            label: 'Name?',
            required: true,
        );
        $description = text('Description?');
        $type = select(
            label: 'Type?',
            options: ['PHYSICAL', 'DIGITAL', 'SERVICE'],
        );
        $category = text('Category? See: https://developer.paypal.com/docs/api/catalog-products/v1/#products_create!ct=application/json&path=category&t=request');
        $imageUrl = text('Image url?');
        $homeUrl = text('Home url?');
        $payload = array_filter([
            'name' => $name,
            'description' => $description,
            'type' => $type,
            'category' => $category,
            'image_url' => $imageUrl,
            'home_url' => $homeUrl,
        ], 'strlen');

        $product = Cashier::api('POST', 'catalogs/products', $payload);

        $this->info('Product created: '.$product['id']);
    }
}
