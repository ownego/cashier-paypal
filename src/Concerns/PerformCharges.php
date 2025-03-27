<?php

namespace Ownego\Cashier\Concerns;

use Ownego\Cashier\Cashier;
use Ownego\Cashier\Exceptions\PaypalException;
use Ownego\Cashier\PaypalSubscription;
use Ownego\Cashier\SubscriptionBuilder;

trait PerformCharges
{
    /**
     * Create a new subscription
     *
     * @throws PaypalException
     */
    public function subscribe(string $subscriptionId)
    {
        $paypalSubscription = new PaypalSubscription(Cashier::api('get', "billing/subscriptions/$subscriptionId")
            ->json());

        if (!$paypalSubscription->active()) {
            throw new \Exception('Subscription is not active');
        }

        $existedSubscription = Cashier::$subscriptionModel
            ::where('paypal_id', $subscriptionId)
            ->first();

        if ($existedSubscription && !$existedSubscription->ownedBy($this)) {
            throw new \Exception('Subscription already exists');
        }

        return $this->subscriptions()->updateOrCreate(
            [
                'paypal_id' => $subscriptionId,
            ],
            [
                'paypal_plan_id' => $paypalSubscription['plan_id'],
                'status' => $paypalSubscription['status'],
                'quantity' => $paypalSubscription['quantity'],
                'trial_ends_at' => $paypalSubscription->trialEndsAt(),
                'ends_at' => null,
            ]
        );
    }

    public function newSubscription($planId, $quantity = 1)
    {
        return new SubscriptionBuilder($this, $planId, $quantity);
    }
}
