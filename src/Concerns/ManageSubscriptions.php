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

    public function subscribed($planId = null)
    {
        $query = $this->subscriptions()->valid();

        if ($planId) {
            $query->where('plan_id', $planId);
        }

        return $query->exists();
    }
}
