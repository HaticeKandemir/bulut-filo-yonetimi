<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Shared by the address cache tables and the services that read/write them,
 * so a lookup always hashes the same canonical form it was stored under.
 */
final class AddressHash
{
    /**
     * mb_strtoupper (not strtoupper) avoids the Turkish locale I/ı pitfall
     * when canonicalising an address before hashing it.
     */
    public static function of(string $value): string
    {
        $canonical = mb_strtoupper(preg_replace('/\s+/u', ' ', trim($value)) ?? '', 'UTF-8');

        return hash('sha256', $canonical);
    }
}
