<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['raw_address_hash', 'raw_address', 'normalized_address'])]
class NormalizedAddress extends Model {}
