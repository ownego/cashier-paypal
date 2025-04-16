<?php

namespace Ownego\Cashier\Concerns;

use Ownego\Cashier\Cashier;
use Ownego\Cashier\Exceptions\PaypalException;
use Ownego\Cashier\PaypalSubscription;
use Ownego\Cashier\SubscriptionBuilder;

trait PerformCharges
{
    /**
     * Subscribe
     *
     * @throws PaypalException
     */
    public function subscribe(string $subscriptionId)
    {
        $this->createAsCustomer();

        return $this->createSubscription($subscriptionId);
    }

    /**
     * Create a new subscription
     *
     * @throws PaypalException
     */
    public function createSubscription(string $subscriptionId)
    {
        $paypalSubscription = new PaypalSubscription(Cashier::api('get', "billing/subscriptions/$subscriptionId")->json());

        if (!$paypalSubscription->active()) {
            throw new \Exception('Subscription is not active');
        }

        $existedSubscription = Cashier::$subscriptionModel
            ::where('paypal_id', $subscriptionId)
            ->first();

        if ($existedSubscription && !$existedSubscription->ownedBy($this)) {
            throw new \Exception('Subscription already exists for another user');
        }

        $paypalPlan = Cashier::api('get', "billing/plans/{$paypalSubscription['plan_id']}")->json();

        return $this->subscriptions()->updateOrCreate(
            [
                'paypal_id' => $subscriptionId,
            ],
            [
                'paypal_product_id' => $paypalPlan['product_id'],
                'paypal_plan_id' => $paypalPlan['id'],
                'status' => $paypalSubscription['status'],
                'quantity' => $paypalSubscription['quantity'],
                'trial_ends_at' => $paypalSubscription->trialEndsAt(),
                'ends_at' => null,
            ]
        );
    }

    public function charge($planId, $quantity, array $options = [])
    {
        $customer = $this->createAsCustomer();

        return Cashier::api('post', 'billing/subscriptions',
            array_replace_recursive([
                'plan_id' => $planId,
                'quantity' => $quantity,
                'subscriber' => [
                    'email_address' => $customer->email,
                ],
                'application_context' => [
                    'brand_name' => config('cashier.brand_name'),
                    'locale' => config('cashier.locale'),
                ],
            ], $options)
        );
    }

    public function newSubscription($planId, $quantity = 1)
    {
        return new SubscriptionBuilder($this, $planId, $quantity);
    }
}
