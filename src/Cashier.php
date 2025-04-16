<?php

namespace Ownego\Cashier;

use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Ownego\Cashier\Exceptions\PaypalException;

class Cashier
{
    protected ?string $accessToken = null;

    protected ?Carbon $accessTokenExpiresAt = null;

    public static string $customerModel = Customer::class;

    public static string $subscriptionModel = Subscription::class;

    public static bool $registerRoutes = true;

    /**
     * Populate access token
     *
     * @return void
     * @throws ConnectionException
     * @throws RequestException
     */
    public function populateAccessToken(): void
    {
        $clientId = config('cashier.client_id');
        $clientSecret = config('cashier.client_secret');
        $host = static::apiUrl();

        $response = Http::withBasicAuth($clientId, $clientSecret)
            ->asForm()
            ->post($host.'/oauth2/token', [
                'grant_type' => 'client_credentials',
            ])
            ->throw();

        $this->accessToken = $response->json('access_token');
        $this->accessTokenExpiresAt = Carbon::now()->addSeconds($response->json('expires_in'));
    }

    /**
     * Check if access token is expired
     *
     * @return bool
     */
    public function isAccessTokenExpired(): bool
    {
        return $this->accessTokenExpiresAt && $this->accessTokenExpiresAt->isPast();
    }

    /**
     * Check if access token is valid
     *
     * @return bool
     */
    public function isAccessTokenValid(): bool
    {
        return $this->accessToken && !$this->isAccessTokenExpired();
    }

    /**
     * Get access token
     *
     * @return string|null
     * @throws ConnectionException
     * @throws RequestException
     */
    public function getAccessToken(): ?string
    {
        if ($this->isAccessTokenValid()) {
            return $this->accessToken;
        }

        $this->populateAccessToken();

        return $this->accessToken;
    }

    /**
     * Call paypal api
     *
     * @param $method
     * @param $uri
     * @param array|null $payload
     * @return Response
     * @throws PaypalException
     */
    public static function api($method, $uri, ?array $payload = null): Response
    {
        $method = strtolower($method);

        $response = Http::acceptJson()
            ->asJson()
            ->withToken(app('cashier')->getAccessToken())
            ->$method(static::apiUrl().'/'.$uri, $payload);

        if ($response->clientError()) {
            throw new PaypalException(
                $response->status(),
                $response->json('error') ?? $response->json('name'),
                $response->json('error_description') ?? $response->json('message'),
            );
        }

        if ($response->serverError()) {
            throw new PaypalException(
                $response->status(),
                $response->json('name'),
                $response->json('message'),
            );
        }

        return $response;
    }

    public static function formRequest($method, $uri, ?array $payload = null): Response
    {
        return Http::accept('application/x-www-form-urlencoded')
            ->withToken(app('cashier')->getAccessToken())
            ->$method(static::apiUrl().'/'.$uri, $payload);
    }

    /**
     * Get api url
     *
     * @return string
     */
    public static function apiUrl(): string
    {
        return 'https://api-m.'.(config('cashier.sandbox') ? 'sandbox.' : '').'paypal.com/v1';
    }

    /**
     * Use subscription model
     *
     * @param  string  $model
     * @return void
     */
    public static function useSubscriptionModel(string $model): void
    {
        static::$subscriptionModel = $model;
    }

    public static function useCustomerModel(string $model): void
    {
        static::$customerModel = $model;
    }

    public static function findBillable($email)
    {
        return (new static::$customerModel)->where('email', $email)->first()?->billable;
    }
}
