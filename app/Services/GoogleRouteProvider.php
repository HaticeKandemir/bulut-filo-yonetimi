<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\RouteProviderInterface;
use App\DTOs\Coordinates;
use App\DTOs\RouteDetails;
use App\Exceptions\RateLimitedException;
use App\Exceptions\RouteComputationException;
use App\Support\ProtobufDuration;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Throwable;

final class GoogleRouteProvider implements RouteProviderInterface
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly RedisFactory $redis,
    ) {}

    public function computeRoute(Coordinates $origin, Coordinates $destination): RouteDetails
    {
        return $this->redis->connection()->throttle('google-routes')
            ->allow(40)
            ->every(1)
            ->block(0)
            ->then(
                fn () => $this->requestRoute($origin, $destination),
                fn () => throw RateLimitedException::throttled(),
            );
    }

    private function requestRoute(Coordinates $origin, Coordinates $destination): RouteDetails
    {
        try {
            $response = $this->http
                ->withHeaders([
                    'X-Goog-Api-Key' => (string) config('services.google_maps.server_key'),
                    'X-Goog-FieldMask' => 'routes.duration,routes.distanceMeters,routes.polyline.encodedPolyline',
                ])
                ->timeout(15)
                ->connectTimeout(5)
                ->retry(3, 200, fn (Throwable $e) => $this->shouldRetry($e))
                ->post('https://routes.googleapis.com/directions/v2:computeRoutes', [
                    'origin' => [
                        'location' => ['latLng' => ['latitude' => $origin->latitude, 'longitude' => $origin->longitude]],
                    ],
                    'destination' => [
                        'location' => ['latLng' => ['latitude' => $destination->latitude, 'longitude' => $destination->longitude]],
                    ],
                    // routingPreference intentionally omitted — defaults to
                    // TRAFFIC_UNAWARE, cheaper and consistent with "computed
                    // once, never recalculated" (a frozen TRAFFIC_AWARE
                    // snapshot would misrepresent live conditions anyway).
                    'travelMode' => 'DRIVE',
                ])
                ->throw();
        } catch (RequestException $e) {
            throw $this->mapRequestException($e);
        } catch (ConnectionException) {
            throw RateLimitedException::upstreamTemporaryError();
        }

        $route = $response->json('routes.0');

        if (! is_array($route) || ! isset($route['distanceMeters'], $route['duration'], $route['polyline']['encodedPolyline'])) {
            throw RouteComputationException::emptyResponse();
        }

        return new RouteDetails(
            distanceMeters: (int) $route['distanceMeters'],
            durationSeconds: ProtobufDuration::seconds((string) $route['duration']),
            polyline: (string) $route['polyline']['encodedPolyline'],
        );
    }

    private function mapRequestException(RequestException $e): Throwable
    {
        $status = $e->response->status();

        return match (true) {
            $status === 429 => RateLimitedException::upstreamOverQuota(),
            $status === 404 => RouteComputationException::notFound(),
            $status === 403 => $this->denied($e->response->body()),
            $status >= 500 => RateLimitedException::upstreamTemporaryError(),
            default => RouteComputationException::invalidArgument(),
        };
    }

    /**
     * PERMISSION_DENIED means the API key/billing/API-enablement is
     * misconfigured — a systemic problem, not a per-row data issue. Log it
     * loudly instead of letting it disappear as one Failed row among many.
     */
    private function denied(string $responseBody): RouteComputationException
    {
        Log::critical('Google Routes request denied — check API key/billing/API enablement.', [
            'body' => $responseBody,
        ]);

        return RouteComputationException::invalidArgument();
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
