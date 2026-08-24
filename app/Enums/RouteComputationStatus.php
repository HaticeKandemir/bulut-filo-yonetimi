<?php

declare(strict_types=1);

namespace App\Enums;

enum RouteComputationStatus: string
{
    case Pending = 'pending';
    case Computed = 'computed';
    case Failed = 'failed';
    case Skipped = 'skipped';
}
