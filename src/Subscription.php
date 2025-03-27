<?php

namespace Ownego\Cashier;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Http\RedirectResponse;
use Ownego\Cashier\Enums\PaypalSubscriptionStatus;

class Subscription extends Model
{
    protected $fillable = [
        'paypal_id',
        'paypal_plan_id',
        'status',
        'quantity',
        'trial_ends_at',
        'ends_at',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * Get the owning billable model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function billable()
    {
        return $this->morphTo();
    }

    public function scopeValid($query)
    {
        return $query->where(function ($query) {
            $query->where('status', PaypalSubscriptionStatus::ACTIVE->value)
                ->orWhere(function ($query) {
                    $query->where('status', PaypalSubscriptionStatus::CANCELLED->value)
                        ->where('ends_at', '>', now());
                });
        });
    }

    public function scopeActive($query)
    {
        return $query->where('status', PaypalSubscriptionStatus::ACTIVE->value);
    }

    public function scopePaused($query)
    {
        return $query->where('status', PaypalSubscriptionStatus::SUSPENDED->value);
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function onGracePeriod(): bool
    {
        return $this->ends_at && $this->ends_at->isFuture();
    }

    public function onPausedGracePeriod(): bool
    {
        return $this->paused_at && $this->paused_at->isFuture();
    }

    public function ownedBy($owner): bool
    {
        return $this->billable->is($owner);
    }

    public function incrementQuantity($quantity = 1, $options = []): RedirectResponse
    {
        return $this->updateQuantity($this->quantity + $quantity, $options);
    }

    public function decrementQuantity($quantity = 1, $options = []): RedirectResponse
    {
        return $this->updateQuantity($this->quantity - $quantity, $options);
    }

    public function updateQuantity($quantity, $options = []): RedirectResponse
    {
        $payload = ['quantity' => $quantity];

        if ($options) {
            $payload['application_context'] = [
                'return_url' => $options['success_url'],
                'cancel_url' => $options['cancel_url'],
            ];
        }

        return $this->redirect($this->updatePaypalSubscription($payload));
    }

    public function swap($planId, $quantity = 1, $options = []): RedirectResponse
    {
        $payload = [
            'plan_id' => $planId,
            'quantity' => $quantity,
        ];

        if ($options) {
            $payload['application_context'] = [
                'return_url' => $options['success_url'],
                'cancel_url' => $options['cancel_url'],
            ];
        }

        return $this->redirect($this->updatePaypalSubscription($payload));
    }

    public function pause($reason): static
    {
        Cashier::api('POST', "billing/subscriptions/$this->paypal_id/suspend", ['reason' => $reason]);

        $paypalSubscription = $this->getPaypalSubscription();

        $this->forceFill([
            'status' => $paypalSubscription['status'],
            'paused_at' => now(),
        ])->save();

        return $this;
    }

    public function resume($reason = null): static
    {
        Cashier::api('POST', "billing/subscriptions/$this->paypal_id/activate", $reason ? ['reason' => $reason] : null)->dd();

        $paypalSubscription = $this->getPaypalSubscription();

        $this->forceFill([
            'status' => $paypalSubscription['status'],
            'paused_at' => null,
        ])->save();

        return $this;
    }

    public function cancel($reason): static
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

    public function updatePaypalSubscription($payload): Response
    {
        return Cashier::api('POST', "billing/subscriptions/$this->paypal_id/revise", $payload);
    }

    public function getPaypalSubscription()
    {
        return new PaypalSubscription(Cashier::api('GET', "billing/subscriptions/$this->paypal_id")->json());
    }

    public function redirect(Response $response): RedirectResponse
    {
        $link = collect($response->json('links'))
            ->first(fn ($link) => $link['rel'] === 'approve');

        return redirect($link['href']);
    }
}
