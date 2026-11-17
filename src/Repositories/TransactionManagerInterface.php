<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories;

interface TransactionManagerInterface
{
    public function run(callable $work): mixed;
}
