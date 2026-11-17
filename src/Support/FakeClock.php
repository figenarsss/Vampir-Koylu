<?php

declare(strict_types=1);

namespace VampirKoylu\Support;

use DateTimeImmutable;

final class FakeClock implements Clock
{
    public function __construct(private DateTimeImmutable $now = new DateTimeImmutable('2026-11-01 20:00:00'))
    {
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function set(DateTimeImmutable $now): void
    {
        $this->now = $now;
    }

    public function advance(int $seconds): void
    {
        $this->now = $this->now->modify("+{$seconds} seconds");
    }
}
