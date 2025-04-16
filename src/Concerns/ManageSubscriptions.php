<?php

namespace Ownego\Cashier\Concerns;

use Ownego\Cashier\Cashier;

trait ManageSubscriptions
{
    public function subscriptions()
    {
        return $this->morphMany(Cashier::$subscriptionModel, 'billable')->orderByDesc('created_at');
    }

    public function subscription($paypalId)
    {
        return $this->subscriptions()->where('paypal_id', $paypalId)->first();
    }

    public function subscriptionByPlan($paypalPlanId)
    {
        return $this->subscriptions()->where('paypal_plan_id', $paypalPlanId)->first();
    }

    public function subscribed($paypalPlanId)
    {
        $subscription = $this->subscriptionByPlan($paypalPlanId);

        return $subscription && $subscription->valid();
    }

    public function subscribedToProduct($paypalProductId, $paypalPlanId)
    {
        $subscription = $this->subscriptionByPlan($paypalPlanId);

        if (!$subscription || !$subscription->valid()) {
            return false;
        }

        return $subscription->hasProduct($paypalProductId);
    }

    public function onTrial($paypalPlanId)
    {
        $subscription = $this->subscription($paypalPlanId);

        return $subscription && $subscription->onTrial();
    }
}
