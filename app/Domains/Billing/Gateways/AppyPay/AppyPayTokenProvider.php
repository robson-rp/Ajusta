<?php

namespace App\Domains\Billing\Gateways\AppyPay;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * OAuth2 client_credentials token for the AppyPay Charges API, cached until
 * a minute before it expires. A lock stops concurrent requests from all
 * fetching a new token at once.
 */
class AppyPayTokenProvider
{
    private const CACHE_KEY = 'billing.appypay.access_token';

    public function token(): string
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        return Cache::lock(self::CACHE_KEY.'.lock', 15)->block(10, function () {
            return Cache::get(self::CACHE_KEY) ?? $this->fetch();
        });
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function fetch(): string
    {
        $config = config('services.appypay');

        $response = Http::asForm()
            ->acceptJson()
            ->timeout(20)
            ->post((string) $config['token_url'], [
                'grant_type' => 'client_credentials',
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'resource' => $config['resource'],
            ]);

        $token = $response->json('access_token');

        if (! $response->successful() || ! is_string($token) || $token === '') {
            // Never include the response body: it may echo credentials.
            throw new RuntimeException('AppyPay authentication failed (HTTP '.$response->status().').');
        }

        $ttl = max(60, (int) $response->json('expires_in', 3600) - 60);
        Cache::put(self::CACHE_KEY, $token, $ttl);

        return $token;
    }
}
