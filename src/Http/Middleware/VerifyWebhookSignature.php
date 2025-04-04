<?php

namespace Ownego\Cashier\Http\Middleware;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class VerifyWebhookSignature
{
    public function handle($request, $next)
    {
        if (! $this->verifySignature($request)) {
            throw new BadRequestHttpException('Invalid signature');
        }

        return $next($request);
    }

    protected function verifySignature($request)
    {
        $transmissionId = $request->header('Paypal-Transmission-Id');
        $transmissionTime = $request->header('Paypal-Transmission-Time');
        $crc = crc32($request->getContent());
        $webhookId = config('cashier.webhook_id');
        $message = "$transmissionId|$transmissionTime|$webhookId|$crc";

        $certPem = $this->downloadAndCache($request->header('Paypal-Cert-Url'));
        $transmissionSig = $request->header('Paypal-Transmission-Sig');

        return openssl_verify($message, base64_decode($transmissionSig), $certPem, OPENSSL_ALGO_SHA256) === 1;
    }

    protected function downloadAndCache($url, $cacheKey = null)
    {
        if (!$cacheKey) {
            $cacheKey = basename($url);
        }

        $pem = file_get_contents($url);

        if (!$pem) {
            throw new \RuntimeException('Failed to download PEM file');
        }

        Cache::remember($cacheKey, 60 * 60 * 24, function () use ($pem) {
            return $pem;
        });

        return $pem;
    }
}
