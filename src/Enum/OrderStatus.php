<?php

declare(strict_types=1);

namespace App\Enum;

enum OrderStatus: string
{
    case NEW = 'NEW';
    case PAID = 'PAID';
    case CANCELLED = 'CANCELLED';
}
