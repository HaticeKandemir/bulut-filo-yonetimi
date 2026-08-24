<?php

declare(strict_types=1);

namespace App\Enums;

enum VehicleStatus: string
{
    case Active = 'active';
    case Passive = 'passive';
    case LeftFleet = 'left_fleet';
}
