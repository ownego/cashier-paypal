<?php

namespace Ownego\Cashier;

use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Client\Response;

class SubscriptionBuilder
{
    protected ?Carbon $startAt = null;

    public function __construct(
        protected $billable,
        protected string $planId,
        protected int $quantity = 1
    ) {
    }

    public function startAt(Carbon $startAt): self
    {
        $this->startAt = $startAt;

        return $this;
    }

    /**
     * Checkout
     */
    public function checkout(array $options = []): RedirectResponse
    {
        return $this->redirect($this->billable->charge(
            $this->planId,
            $this->quantity,
            $this->parseOptions($options),
        ));
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

        if ($this->startAt && $this->startAt->isFuture()) {
            $payload['start_time'] = $this->startAt->toIso8601String();
        }

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
