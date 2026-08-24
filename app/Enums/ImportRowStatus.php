<?php

declare(strict_types=1);

namespace App\Enums;

enum ImportRowStatus: string
{
    case Pending = 'pending';
    case Processed = 'processed';
    case NeedsReview = 'needs_review';
    case Failed = 'failed';
}
