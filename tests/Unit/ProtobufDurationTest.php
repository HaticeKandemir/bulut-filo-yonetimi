<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\RouteComputationException;
use App\Support\ProtobufDuration;
use Tests\TestCase;

class ProtobufDurationTest extends TestCase
{
    public function test_parses_whole_seconds(): void
    {
        $this->assertSame(165, ProtobufDuration::seconds('165s'));
    }

    public function test_rounds_fractional_seconds(): void
    {
        $this->assertSame(165, ProtobufDuration::seconds('165.400s'));
    }

    public function test_throws_on_malformed_input(): void
    {
        $this->expectException(RouteComputationException::class);

        ProtobufDuration::seconds('not-a-duration');
    }
}
