<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories\Pdo;

use PDO;
use Throwable;
use VampirKoylu\Repositories\TransactionManagerInterface;

final class PdoTransactionManager implements TransactionManagerInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function run(callable $work): mixed
    {
        // Zaten bir transaction içindeysek ikinci bir tane açmayız.
        if ($this->pdo->inTransaction()) {
            return $work();
        }

        $this->pdo->beginTransaction();
        try {
            $result = $work();
            $this->pdo->commit();

            return $result;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }
}
