<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Exceptions\InvalidImportRowException;

final readonly class VehicleImportRowData
{
    private function __construct(
        public string $vin,
        public string $plate,
        public string $brand,
        public string $model,
        public string $institutionCode,
    ) {}

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromRawRow(array $raw): self
    {
        $vin = self::normalize($raw['sasi_no'] ?? null);
        $plate = self::normalize($raw['plaka'] ?? null);
        $brand = self::trimmed($raw['marka'] ?? null);
        $model = self::trimmed($raw['model'] ?? null);
        $institutionCode = self::normalize($raw['kurum_kodu'] ?? null);

        foreach (['sasi_no' => $vin, 'plaka' => $plate, 'marka' => $brand, 'model' => $model, 'kurum_kodu' => $institutionCode] as $field => $value) {
            if ($value === '') {
                throw InvalidImportRowException::missingField($field);
            }
        }

        return new self($vin, $plate, $brand, $model, $institutionCode);
    }

    /**
     * mb_strtoupper (not strtoupper/ucfirst) avoids the Turkish locale I/ı
     * pitfall when normalising VIN, plate, and institution code.
     */
    private static function normalize(mixed $value): string
    {
        return mb_strtoupper(trim((string) $value), 'UTF-8');
    }

    private static function trimmed(mixed $value): string
    {
        return trim((string) $value);
    }
}
