<?php

namespace Ownego\Cashier\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Ownego\Cashier\Cashier;
use Ownego\Cashier\Events\SaleCompleted;
use Ownego\Cashier\Events\SaleRefunded;
use Ownego\Cashier\Events\SaleReversed;
use Ownego\Cashier\Events\SubscriptionActivated;
use Ownego\Cashier\Events\SubscriptionCancelled;
use Ownego\Cashier\Events\SubscriptionCreated;
use Ownego\Cashier\Events\SubscriptionExpired;
use Ownego\Cashier\Events\SubscriptionPaused;
use Ownego\Cashier\Events\WebhookHandled;
use Ownego\Cashier\Events\WebhookReceived;
use Ownego\Cashier\Http\Middleware\VerifyWebhookSignature;
use Ownego\Cashier\PaypalSubscription;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    public function __construct()
    {
        if (config('cashier.webhook_id') !== null) {
            $this->middleware(VerifyWebhookSignature::class);
        }
    }

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

    protected function handleBillingSubscriptionCreated($payload)
    {
        $resource = $payload['resource'];

        $billable = $this->findBillable($resource['subscriber']['email_address']);

        if (! $billable) {
            return;
        }

        $paypalPlan = Cashier::api('get', 'billing/plans/'.$resource['plan_id'])->json();

        $subscription = $billable->subscriptions()->updateOrCreate(
            [
                'paypal_id' => $resource['id'],
            ],
            [
                'paypal_plan_id' => $paypalPlan['id'],
                'status' => $resource['status'],
                'quantity' => $resource['quantity'],
                'trial_ends_at' => null,
                'paused_at' => null,
                'ends_at' => null,
            ]
        );

        SubscriptionCreated::dispatch($subscription, $payload);
    }

    protected function handleBillingSubscriptionActivated($payload)
    {
        $resource = $payload['resource'];

        if (! $subscription = $this->findSubscription($resource['id'])) {
            return;
        }

        $subscription->status = $resource['status'];
        $subscription->paused_at = null;
        $subscription->ends_at = null;
        $subscription->save();

        SubscriptionActivated::dispatch($subscription, $payload);
    }

    protected function handleBillingSubscriptionExpired($payload)
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

    protected function handleBillingSubscriptionCancelled($payload)
    {
        $resource = $payload['resource'];

        if (! $subscription = $this->findSubscription($resource['id'])) {
            return;
        }

        $paypalSubscription = new PaypalSubscription($payload['resource']);
        $subscription->status = $resource['status'];
        $subscription->paused_at = null;
        $subscription->ends_at = $subscription->onTrial() ? $subscription->trial_ends_at : $paypalSubscription->nextBillingTime();
        $subscription->save();

        SubscriptionCancelled::dispatch($subscription, $payload);
    }

    protected function handleBillingSubscriptionSuspended($payload)
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

    protected function handleBillingSubscriptionPaymentFailed($payload)
    {
        $resource = $payload['resource'];

        if (! $subscription = $this->findSubscription($resource['id'])) {
            return;
        }

        $subscription->status = $resource['status'];
        $subscription->save();
    }

    protected function handlePaymentSaleCompleted($payload)
    {
        if ($sale = $this->handlePaymentSaleUpdated($payload)) {
            SaleCompleted::dispatch($sale, $payload);
        }
    }

    protected function handlePaymentSaleRefunded($payload)
    {
        if ($sale = $this->handlePaymentSaleUpdated($payload)) {
            SaleRefunded::dispatch($sale, $payload);
        }
    }

    protected function handlePaymentSaleReversed($payload)
    {
        if ($sale = $this->handlePaymentSaleUpdated($payload)) {
            SaleReversed::dispatch($sale, $payload);
        }
    }

    protected function handlePaymentSaleUpdated($payload)
    {
        $resource = $payload['resource'];
        $ba_id = $resource['billing_agreement_id'];

        $subscription = $this->findSubscription($ba_id);

        if (! $subscription) {
            return null;
        }

        $billable = $subscription->billable;

        return $billable->sales()->updateOrCreate(
            [
                'paypal_id' => $resource['id'],
            ],
            [
                'paypal_subscription_id' => $ba_id,
                'amount' => $resource['amount']['total'],
                'currency' => $resource['amount']['currency'],
                'state' => $resource['state'],
                'created_at' => Carbon::parse($resource['create_time']),
            ]
        );
    }

    protected function subscriptionExists($paypalId)
    {
        return Cashier::$subscriptionModel::where('paypal_id', $paypalId)->exists();
    }

    protected function findSubscription($paypalId)
    {
        return Cashier::$subscriptionModel::firstWhere('paypal_id', $paypalId);
    }

    protected function findBillable($email)
    {
        return Cashier::$customerModel::where('email', $email)->first()?->billable;
    }
}
