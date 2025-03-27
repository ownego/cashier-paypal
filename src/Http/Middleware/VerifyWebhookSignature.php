<?php

namespace Ownego\Cashier\Http\Middleware;

use Illuminate\Routing\Controllers\Middleware;

class VerifyWebhookSignature extends Middleware
{
    public function handle($request, $next)
    {
        return $next($request);
    }
}
