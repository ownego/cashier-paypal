<?php

namespace Ownego\Cashier;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Client\Response;

class SubscriptionBuilder
{
    public function __construct(
        protected $billable,
        protected string $planId,
        protected int $quantity = 1
    ) {
    }

    public function checkout(array $options = [])
    {
        $payload = [
            'plan_id' => $this->planId,
            'quantity' => $this->quantity,
            'subscriber' => [
                'email_address' => $this->billable->email,
            ],
            'application_context' => array_merge([
                'brand_name' => config('cashier.brand_name'),
                'locale' => config('cashier.locale'),
                'return_url' => $options['success_url'] ?? route('home'),
                'cancel_url' => $options['cancel_url'] ?? route('home'),
            ], $options['application_context'] ?? []),
        ];

        $response = Cashier::api('post', 'billing/subscriptions', $payload);

        return $this->redirect($response);
    }

    public function redirect(Response $response): RedirectResponse
    {
        $link = collect($response->json('links'))
            ->first(fn ($link) => $link['rel'] === 'approve');

        return redirect($link['href']);
    }
}
