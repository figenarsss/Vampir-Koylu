<?php

declare(strict_types=1);

namespace VampirKoylu\Support;

use DateTimeImmutable;

interface Clock
{
    public function now(): DateTimeImmutable;
}
