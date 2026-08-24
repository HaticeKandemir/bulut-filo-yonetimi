<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\GeocoderInterface;
use App\DTOs\Coordinates;
use App\Exceptions\AddressResolutionException;
use App\Exceptions\RateLimitedException;
use App\Models\GeocodedAddress;
use App\Support\AddressHash;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Throwable;

final class GoogleGeocoder implements GeocoderInterface
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly RedisFactory $redis,
    ) {}

    public function geocode(string $normalizedAddress): Coordinates
    {
        $hash = AddressHash::of($normalizedAddress);

        $cached = GeocodedAddress::where('normalized_address_hash', $hash)->first();

        if ($cached !== null) {
            return new Coordinates($cached->latitude, $cached->longitude);
        }

        $coordinates = $this->redis->connection()->throttle('google-geocoding')
            ->allow(40)
            ->every(1)
            ->block(0)
            ->then(
                fn () => $this->requestGeocode($normalizedAddress),
                fn () => throw RateLimitedException::throttled(),
            );

        GeocodedAddress::create([
            'normalized_address_hash' => $hash,
            'normalized_address' => $normalizedAddress,
            'latitude' => $coordinates->latitude,
            'longitude' => $coordinates->longitude,
        ]);

        return $coordinates;
    }

    private function requestGeocode(string $normalizedAddress): Coordinates
    {
        try {
            $response = $this->http
                ->timeout(15)
                ->connectTimeout(5)
                ->retry(3, 200, fn (Throwable $e) => $this->shouldRetry($e))
                ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $normalizedAddress,
                    'key' => (string) config('services.google_maps.server_key'),
                ])
                ->throw();
        } catch (RequestException $e) {
            $status = $e->response->status();

            if ($status === 429) {
                throw RateLimitedException::upstreamOverQuota();
            }

            // A 5xx surviving retry() is still a transient upstream problem,
            // not a permanent address-data problem — don't fail the row.
            if ($status >= 500) {
                throw RateLimitedException::upstreamTemporaryError();
            }

            throw AddressResolutionException::invalidRequest($normalizedAddress);
        } catch (ConnectionException) {
            throw RateLimitedException::upstreamTemporaryError();
        }

        // Google Geocoding reports failures via the "status" field in a 200
        // OK body — Http::retry()'s $when callback never sees these, so
        // they must be handled explicitly rather than relying on retry().
        return match ($response->json('status')) {
            'OK' => new Coordinates(
                (float) $response->json('results.0.geometry.location.lat'),
                (float) $response->json('results.0.geometry.location.lng'),
            ),
            'ZERO_RESULTS' => throw AddressResolutionException::zeroResults($normalizedAddress),
            'INVALID_REQUEST' => throw AddressResolutionException::invalidRequest($normalizedAddress),
            'OVER_QUERY_LIMIT', 'UNKNOWN_ERROR' => throw RateLimitedException::upstreamOverQuota(),
            'REQUEST_DENIED' => $this->denyAndThrow($normalizedAddress, (string) $response->json('error_message')),
            default => throw AddressResolutionException::invalidRequest($normalizedAddress),
        };
    }

    /**
     * REQUEST_DENIED means the API key/billing is misconfigured — a systemic
     * problem, not a per-row data issue. Log it loudly instead of letting it
     * disappear as one Failed row among many.
     */
    private function denyAndThrow(string $normalizedAddress, string $errorMessage): never
    {
        Log::critical('Google Geocoding request denied — check API key/billing.', [
            'error_message' => $errorMessage,
        ]);

        throw AddressResolutionException::invalidRequest($normalizedAddress);
    }

    private function shouldRetry(Throwable $exception): bool
    {
        if ($exception instanceof RequestException) {
            $status = $exception->response->status();

            return $status === 429 || $status >= 500;
        }

        return true;
    }
}
