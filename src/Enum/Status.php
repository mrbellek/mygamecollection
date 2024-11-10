<?php
declare(strict_types=1);

namespace App\Enum;

class Status
{
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_DELISTED = 'delisted';
    public const STATUS_REGION_LOCKED = 'region-locked';
    public const STATUS_SALE = 'sale';
    public const STATUS_SOLD = 'sold';
}