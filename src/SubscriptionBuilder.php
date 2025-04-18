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
        return $this->redirect($this->billable->charge(
            $this->planId,
            $this->quantity,
            [
                'application_context' => [
                    'return_url' => $options['success_url'] ?? route('home'),
                    'cancel_url' => $options['cancel_url'] ?? route('home'),
                ]
            ],
        ));
    }

    public function redirect(Response $response): RedirectResponse
    {
        $link = collect($response->json('links'))
            ->first(fn ($link) => $link['rel'] === 'approve');

        return redirect($link['href']);
    }
}
