<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

final class InvalidCredentialsException extends RuntimeException
{
    public static function make(): self
    {
        return new self('Invalid email or password.');
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 401);
    }
}
