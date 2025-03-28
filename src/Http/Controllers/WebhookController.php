<?php

namespace Ownego\Cashier\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
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

    public function handleBillingSubscriptionActivated($payload)
    {

    }
}
