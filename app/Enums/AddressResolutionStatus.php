<?php

declare(strict_types=1);

namespace App\Enums;

enum AddressResolutionStatus: string
{
    case Pending = 'pending';
    case Resolved = 'resolved';
    case Failed = 'failed';
    case Skipped = 'skipped';
}
