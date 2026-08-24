<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Completed means row processing finished, not that every row succeeded —
 * a Completed batch may still contain NeedsReview or Failed rows.
 */
enum ImportBatchStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
}
