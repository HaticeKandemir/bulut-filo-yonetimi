<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InvalidCredentialsException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

final class AuthService
{
    /**
     * @throws InvalidCredentialsException when the email/password pair does not match a user.
     */
    public function attempt(string $email, string $password): string
    {
        $user = User::where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw InvalidCredentialsException::make();
        }

        return $user->createToken('api')->plainTextToken;
    }
}
