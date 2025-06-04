<?php

namespace Ownego\Cashier;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Client\Response;

class SubscriptionBuilder
{
    protected int $quantity = 1;

    public function __construct(
        protected $billable,
        protected string $planId,
        protected string $type = 'default',
    ) {
    }

    public function quantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Checkout
     */
    public function checkout(array $options = []): RedirectResponse
    {
        $response = $this->billable->charge(
            $this->planId,
            $this->quantity,
            $this->parseOptions($options),
        );

        if ($response->failed()) {
            throw new \RuntimeException('Failed to create subscription: '.$response->body());
        }

        $this->billable->subscriptions()->create([
            'type' => $this->type,
            'paypal_id' => $response->json('id'),
            'paypal_plan_id' => $this->planId,
            'status' => $response->json('status'),
            'quantity' => $this->quantity,
        ]);

        return $this->redirect($response);
    }

    /**
     * Parse options
     */
    protected function parseOptions(array $options): array
    {
        $payload = [
            'application_context' => [
                'return_url' => $options['success_url'] ?? route('home'),
                'cancel_url' => $options['cancel_url'] ?? route('home'),
            ]
        ];

        return $payload;
    }

    /**
     * Create redirect response
     */
    public function redirect(Response $response): RedirectResponse
    {
        $link = collect($response->json('links'))
            ->first(fn ($link) => $link['rel'] === 'approve');

        return redirect($link['href']);
    }
}
