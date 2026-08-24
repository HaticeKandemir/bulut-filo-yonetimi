<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\AddressFormatterInterface;
use App\Exceptions\AddressResolutionException;
use App\Exceptions\RateLimitedException;
use App\Models\NormalizedAddress;
use App\Support\AddressHash;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Throwable;

final class OpenAiAddressFormatter implements AddressFormatterInterface
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly RedisFactory $redis,
    ) {}

    public function normalize(string $rawAddress): string
    {
        $hash = AddressHash::of($rawAddress);

        $cached = NormalizedAddress::where('raw_address_hash', $hash)->first();

        if ($cached !== null) {
            return $cached->normalized_address;
        }

        $normalized = $this->redis->connection()->throttle('openai-address-normalize')
            ->allow(10)
            ->every(1)
            ->block(0)
            ->then(
                fn () => $this->requestNormalization($rawAddress),
                fn () => throw RateLimitedException::throttled(),
            );

        NormalizedAddress::create([
            'raw_address_hash' => $hash,
            'raw_address' => $rawAddress,
            'normalized_address' => $normalized,
        ]);

        return $normalized;
    }

    private function requestNormalization(string $rawAddress): string
    {
        try {
            $response = $this->http
                ->withToken((string) config('services.openai.api_key'))
                ->timeout(15)
                ->connectTimeout(5)
                ->retry(3, 200, fn (Throwable $e) => $this->shouldRetry($e))
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'temperature' => 0,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You standardise Turkish postal addresses into the format '
                                .'"Mahalle, Cadde/Sokak No, İlçe/İl". If the input is not a real, '
                                .'resolvable address, set is_resolvable to false.',
                        ],
                        ['role' => 'user', 'content' => $rawAddress],
                    ],
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => [
                            'name' => 'normalized_address',
                            'strict' => true,
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'normalized_address' => ['type' => 'string'],
                                    'is_resolvable' => ['type' => 'boolean'],
                                ],
                                'required' => ['normalized_address', 'is_resolvable'],
                                'additionalProperties' => false,
                            ],
                        ],
                    ],
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

            throw AddressResolutionException::notResolvable($rawAddress);
        } catch (ConnectionException) {
            throw RateLimitedException::upstreamTemporaryError();
        }

        $payload = json_decode((string) $response->json('choices.0.message.content'), true);

        if (! is_array($payload) || ($payload['is_resolvable'] ?? false) !== true) {
            throw AddressResolutionException::notResolvable($rawAddress);
        }

        return (string) $payload['normalized_address'];
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
