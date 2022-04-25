<?php
namespace Saq\Database\Adapter;

use PDO;
use Saq\Database\AdapterInterface;

class PDOAdapter implements AdapterInterface
{
    /**
     * @var PDO
     */
    protected PDO $connection;

    /**
     * @param string $dsn
     * @param string|null $username
     * @param string|null $password
     */
    public function __construct(string $dsn, ?string $username = null, ?string $password = null)
    {
        $this->connection = new PDO($dsn, $username, $password);
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    /**
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * @inheritDoc
     */
    public function execute(string $query): bool
    {
        $stmt = $this->connection->prepare($query);
        return $stmt->execute();
    }

    /**
     * @inheritDoc
     */
    public function fetchRow(string $query): array
    {
        $stmt = $this->connection->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row === false ? [] : $row;
    }

    /**
     * @inheritDoc
     */
    public function fetchAll(string $query): array
    {
        $stmt = $this->connection->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * @inheritDoc
     */
    public function fetchOne(string $query, int $columnIndex = 0): mixed
    {
        $stmt = $this->connection->prepare($query);
        $stmt->execute();
        $value = $stmt->fetchColumn($columnIndex);
        return $value === false ? null : $value;
    }

    /**
     * @inheritDoc
     */
    public function fetchColumn(string $query, int $columnIndex = 0): array
    {
        $stmt = $this->connection->prepare($query);
        $stmt->execute();
        $values = [];

        while (($value = $stmt->fetchColumn($columnIndex)) !== false)
        {
            $values[] = $value;
        }

        return $values;
    }

    /**
     * @return int
     */
    public function getLastInsertId(): int
    {
        return (int)$this->connection->lastInsertId();
    }

    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollBack(): void
    {
        $this->connection->rollBack();
    }
}