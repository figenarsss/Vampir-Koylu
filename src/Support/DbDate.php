<?php

declare(strict_types=1);

namespace VampirKoylu\Support;

use DateTimeImmutable;

final class DbDate
{
    public const FORMAT = 'Y-m-d H:i:s';

    public static function format(DateTimeImmutable $date): string
    {
        return $date->format(self::FORMAT);
    }
}
