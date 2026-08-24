<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\AddressFormatterInterface;
use App\Contracts\GeocoderInterface;
use App\Enums\AddressResolutionStatus;
use App\Exceptions\AddressResolutionException;
use App\Models\GeocodedAddress;
use App\Models\ImportRow;
use App\Support\AddressHash;

final class AddressResolutionService
{
    public function __construct(
        private readonly AddressFormatterInterface $formatter,
        private readonly GeocoderInterface $geocoder,
    ) {}

    /**
     * Resolves a row's start/end addresses. Never throws for domain failures
     * (recorded on the row as Failed); RateLimitedException is left to
     * propagate so the caller can release the job back to the queue.
     */
    public function resolveRow(ImportRow $row): void
    {
        $raw = $row->raw_data;
        $start = trim((string) ($raw['baslangic_adresi'] ?? ''));
        $end = trim((string) ($raw['bitis_adresi'] ?? ''));

        if ($start === '' && $end === '') {
            $row->update(['address_resolution_status' => AddressResolutionStatus::Skipped]);

            return;
        }

        try {
            $startAddressId = $start !== '' ? $this->resolveAddress($start)->id : null;
            $endAddressId = $end !== '' ? $this->resolveAddress($end)->id : null;

            $row->update([
                'start_geocoded_address_id' => $startAddressId,
                'end_geocoded_address_id' => $endAddressId,
                'address_resolution_status' => AddressResolutionStatus::Resolved,
            ]);
        } catch (AddressResolutionException $e) {
            $row->update([
                'address_resolution_status' => AddressResolutionStatus::Failed,
                'address_resolution_error' => $e->getMessage(),
            ]);
        }
    }

    private function resolveAddress(string $rawAddress): GeocodedAddress
    {
        $normalized = $this->formatter->normalize($rawAddress);

        // geocode() persists the GeocodedAddress row as a cache side effect
        // (find-or-create by hash); by the time it returns, the row for
        // $normalized's hash is guaranteed to exist.
        $this->geocoder->geocode($normalized);

        return GeocodedAddress::where('normalized_address_hash', AddressHash::of($normalized))->firstOrFail();
    }
}
