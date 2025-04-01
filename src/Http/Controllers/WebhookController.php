<?php

namespace Ownego\Cashier\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Ownego\Cashier\Cashier;
use Ownego\Cashier\Events\SubscriptionCancelled;
use Ownego\Cashier\Events\SubscriptionExpired;
use Ownego\Cashier\Events\SubscriptionPaused;
use Ownego\Cashier\Events\WebhookHandled;
use Ownego\Cashier\Events\WebhookReceived;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        $payload = $request->all();

        WebhookReceived::dispatch($payload);

        $method = 'handle'.Str::studly(Str::replace('.', ' ', Str::lower($payload['event_type'])));

        if (method_exists($this, $method)) {
            $this->{$method}($payload);

            WebhookHandled::dispatch($payload);

            return new Response('Webhook Handled');
        }

        return new Response();
    }

    public function handleBillingSubscriptionCreated($payload)
    {
        $resource = $payload['resource'];

        if ($this->subscriptionExists($resource['id'])) {
            return;
        }

        if (! $billable = $this->findBillable($resource['subscriber']['email'])) {
            return;
        }

        $subscription = $billable->subscriptions()->create([
            'paypal_id' => $resource['id'],
            'paypal_plan_id' => $resource['plan_id'],
            'status' => $resource['status'],
            'quantity' => $resource['quantity'],

        ]);
    }

    public function handleBillingSubscriptionExpired($payload)
    {
        $resource = $payload['resource'];

        if (! $subscription = $this->findSubscription($resource['id'])) {
            return;
        }

        $subscription->status = $resource['status'];
        $subscription->paused_at = null;
        $subscription->ends_at = now();
        $subscription->save();

        SubscriptionExpired::dispatch($subscription, $payload);
    }

    public function handleBillingSubscriptionCancelled($payload)
    {
        $resource = $payload['resource'];

        if (! $subscription = $this->findSubscription($resource['id'])) {
            return;
        }

        $subscription->status = $resource['status'];
        $subscription->paused_at = null;
        $subscription->ends_at = Carbon::parse($resource['billing_info']['next_billing_time'], 'UTC');
        $subscription->save();

        SubscriptionCancelled::dispatch($subscription, $payload);
    }

    public function handleBillingSubscriptionSuspended($payload)
    {
        $resource = $payload['resource'];

        if (! $subscription = $this->findSubscription($resource['id'])) {
            return;
        }

        $subscription->status = $resource['status'];
        $subscription->paused_at = $resource['status_update_time'];
        $subscription->ends_at = null;
        $subscription->save();

        SubscriptionPaused::dispatch($subscription, $payload);
    }

    public function handleBillingSubscriptionPaymentFailed($payload)
    {
        $resource = $payload['resource'];

        if (! $subscription = $this->findSubscription($resource['id'])) {
            return;
        }

        $subscription->status = $resource['status'];
        $subscription->save();
    }

    public function subscriptionExists($paypalId)
    {
        return Cashier::$subscriptionModel::where('paypal_id', $paypalId)->exists();
    }

    public function findSubscription($paypalId)
    {
        return Cashier::$subscriptionModel::firstWhere('paypal_id', $paypalId);
    }

    public function findBillable($paypalId)
    {
        return Cashier::findBillable($paypalId);
    }
}
