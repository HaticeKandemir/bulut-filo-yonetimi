<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * A transient failure (local throttle, upstream OVER_QUERY_LIMIT, or a
 * temporary upstream error) — not a domain error. The queued job should
 * release() itself rather than marking the row Failed.
 */
final class RateLimitedException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $retryAfterSeconds,
    ) {
        parent::__construct($message);
    }

    public static function throttled(int $retryAfterSeconds = 30): self
    {
        return new self('Local throttle limit reached.', $retryAfterSeconds);
    }

    public static function upstreamOverQuota(int $retryAfterSeconds = 30): self
    {
        return new self('Upstream service reported OVER_QUERY_LIMIT.', $retryAfterSeconds);
    }

    public static function upstreamTemporaryError(int $retryAfterSeconds = 15): self
    {
        return new self('Upstream service reported a temporary error.', $retryAfterSeconds);
    }
}
