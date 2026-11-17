<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories\Pdo;

use PDO;

abstract class PdoRepository
{
    public function __construct(protected readonly PDO $pdo)
    {
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    protected function execute(string $sql, array $params = []): int
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->rowCount();
    }

    protected function insert(string $sql, array $params): int
    {
        $this->execute($sql, $params);

        return (int) $this->pdo->lastInsertId();
    }
}
