<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories\Json;

use VampirKoylu\Repositories\TransactionManagerInterface;

final class JsonTransactionManager implements TransactionManagerInterface
{
    public function __construct(private readonly JsonStorage $storage)
    {
    }

    public function run(callable $work): mixed
    {
        return $this->storage->transaction($work);
    }
}
