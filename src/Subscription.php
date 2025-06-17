<?php

namespace Ownego\Cashier;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Client\Response;
use Illuminate\Http\RedirectResponse;
use Ownego\Cashier\Enums\PaypalSubscriptionStatus;

class Subscription extends Model
{
    /**
     * @inheritdoc
     */
    protected $fillable = [
        'type',
        'paypal_id',
        'paypal_plan_id',
        'status',
        'quantity',
        'trial_ends_at',
        'ends_at',
    ];

    /**
     * @inheritdoc
     */
    protected $casts = [
        'trial_ends_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * Get the owning billable model.
     */
    public function billable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get valid attribute.
     */
    public function valid(): bool
    {
        return $this->status === PaypalSubscriptionStatus::ACTIVE->value
            || ($this->status === PaypalSubscriptionStatus::CANCELLED->value && $this->ends_at && $this->ends_at->isFuture());
    }

    /**
     * Scope valid
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where(function ($query) {
            $query->where('status', PaypalSubscriptionStatus::ACTIVE->value)
                ->orWhere(function ($query) {
                    $query->where('status', PaypalSubscriptionStatus::CANCELLED->value)
                        ->where('ends_at', '>', now());
                });
        });
    }

    /**
     * Scope active
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', PaypalSubscriptionStatus::ACTIVE->value);
    }

    /**
     * Scope paused
     */
    public function scopePaused(Builder $query): Builder
    {
        return $query->where('status', PaypalSubscriptionStatus::SUSPENDED->value);
    }

    /**
     * On trial
     */
    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * On grace period
     */
    public function onGracePeriod(): bool
    {
        return $this->ends_at && $this->ends_at->isFuture();
    }

    /**
     * On paused grace period
     */
    public function onPausedGracePeriod(): bool
    {
        return $this->paused_at && $this->paused_at->isFuture();
    }

    /**
     * Determine if the subscription is owned by the given owner.
     */
    public function ownedBy($owner): bool
    {
        return $this->billable->is($owner);
    }

    /**
     * Increment quantity
     */
    public function incrementQuantity($quantity = 1, $options = []): RedirectResponse|static
    {
        return $this->updateQuantity($this->quantity + $quantity, $options);
    }

    /**
     * Decrement quantity
     */
    public function decrementQuantity($quantity = 1, $options = []): RedirectResponse|static
    {
        return $this->updateQuantity($this->quantity - $quantity, $options);
    }

    /**
     * Update quantity
     */
    public function updateQuantity($quantity, $options = []): RedirectResponse|static
    {
        $payload = array_merge([
            'quantity' => $quantity,
        ], $this->parseOptions($options));

        $response = $this->updatePaypalSubscription($payload);

        if ($redirect = $this->redirect($response)) {
            return $redirect;
        }

        $this->forceFill([
            'quantity' => $response['quantity'],
        ])->save();

        return $this;
    }

    /**
     * Swap plan
     */
    public function swap($planId, $quantity = 1, $options = []): RedirectResponse|static
    {
        $payload = array_merge([
            'plan_id' => $planId,
            'quantity' => $quantity,
        ], $this->parseOptions($options));

        $response = $this->updatePaypalSubscription($payload);

        if ($redirect = $this->redirect($response)) {
            return $redirect;
        }

        $this->forceFill([
            'paypal_plan_id' => $response['plan_id'],
            'quantity' => $response['quantity'],
        ]);

        return $this;
    }

    /**
     * Pause subscription
     */
    public function pause($reason = 'User requested'): static
    {
        Cashier::api('POST', "billing/subscriptions/$this->paypal_id/suspend", ['reason' => $reason]);

        $paypalSubscription = $this->getPaypalSubscription();

        $this->forceFill([
            'status' => $paypalSubscription['status'],
            'paused_at' => now(),
        ])->save();

        return $this;
    }

    /**
     * Resume subscription
     */
    public function resume($reason = null): static
    {
        Cashier::api('POST', "billing/subscriptions/$this->paypal_id/activate", $reason ? ['reason' => $reason] : null);

        $paypalSubscription = $this->getPaypalSubscription();

        $this->forceFill([
            'status' => $paypalSubscription['status'],
            'paused_at' => null,
        ])->save();

        return $this;
    }

    /**
     * Cancel subscription
     */
    public function cancel($reason = 'User requested'): static
    {
        $paypalSubscription = $this->getPaypalSubscription();
        $ends_at = $paypalSubscription->nextBillingTime();

        Cashier::api('POST', "billing/subscriptions/$this->paypal_id/cancel", ['reason' => $reason]);

        $this->forceFill([
            'status' => PaypalSubscriptionStatus::CANCELLED->value,
            'ends_at' => $ends_at,
        ])->save();

        return $this;
    }

    /**
     * Update paypal subscription
     */
    public function updatePaypalSubscription($payload): Response
    {
        return Cashier::api('POST', "billing/subscriptions/$this->paypal_id/revise", $payload);
    }

    /**
     * Get paypal subscription
     */
    public function getPaypalSubscription(): PaypalSubscription
    {
        return new PaypalSubscription(Cashier::api('GET', "billing/subscriptions/$this->paypal_id")->json());
    }

    /**
     * Redirect to PayPal
     */
    public function redirect(Response $response): ?RedirectResponse
    {
        $link = collect($response->json('links'))
            ->first(fn ($link) => $link['rel'] === 'approve');

        if (! $link) {
            return null;
        }

        return redirect($link['href']);
    }

    public function parseOptions(array $options): array
    {
        $payload = [
            'application_context' => [
                'brand_name' => config('cashier.brand_name'),
                'locale' => config('cashier.locale'),
            ],
        ];

        if (isset($options['success_url'])) {
            $payload['application_context']['return_url'] = $options['success_url'];
        }

        if (isset($options['cancel_url'])) {
            $payload['application_context']['cancel_url'] = $options['cancel_url'];
        }

        return $payload;
    }

    public function hasPlan(string $planId): bool
    {
        return $this->paypal_plan_id === $planId;
    }
}
