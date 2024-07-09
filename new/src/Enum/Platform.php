<?php
declare(strict_types=1);

namespace App\Enum;

/**
 * TODO:
 * - convert into enum once provider upgrades to PHP 8.2
 * - backed enum with raw platform values?
 */

class Platform
{
    public const PLATFORM_XB1 = 'Xbox One';
    public const PLATFORM_360 = 'Xbox 360';
    public const PLATFORM_XSX = 'Xbox Series X|S';
    public const PLATFORM_WIN = 'Windows';
    public const PLATFORM_ANDROID = 'Android';
    public const PLATFORM_WEB = 'Web';
    public const PLATFORM_SWITCH = 'Nintendo Switch';
}