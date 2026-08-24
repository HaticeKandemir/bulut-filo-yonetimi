<?php

declare(strict_types=1);

namespace App\Imports;

use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Marker class for Excel::toCollection() — turns each row into an
 * associative array keyed by the sheet's heading row (sasi_no, plaka,
 * marka, model, kurum_kodu, ...).
 */
final class VehicleRowsImport implements Import, WithHeadingRow {}
