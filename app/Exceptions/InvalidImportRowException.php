<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class InvalidImportRowException extends RuntimeException
{
    public static function missingField(string $field): self
    {
        return new self("Import row is missing required field \"{$field}\".");
    }

    public static function unknownInstitutionCode(string $code): self
    {
        return new self("Unknown institution code \"{$code}\".");
    }
}
