<?php
namespace Saq\Database;

use JetBrains\PhpStorm\Pure;
use ReflectionClass;
use RuntimeException;
use Saq\Database\Query\DeleteStatement;
use Saq\Database\Query\Expression;
use Saq\Database\Query\InsertStatement;
use Saq\Database\Query\SelectStatement;
use Saq\Database\Query\Union;
use Saq\Database\Query\UpdateStatement;
use Saq\Interfaces\ContainerInterface;

abstract class Repository
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @var AdapterInterface
     */
    private AdapterInterface $adapter;

    /**
     * @var Table
     */
    private Table $table;

    /**
     * @param AdapterInterface $adapter
     * @param ContainerInterface $container
     */
    public function __construct(AdapterInterface $adapter, ContainerInterface $container)
    {
        $this->container = $container;
        $this->adapter = $adapter;
        $reflection = new ReflectionClass($this);
        $attributes = $reflection->getAttributes(Table::class);

        if (count($attributes) === 1)
        {
            /** @var Table $table */
            $table = $attributes[0]->newInstance();
            $this->table = $table;
        }
        else
        {
            throw new RuntimeException(sprintf('Repository class requires attribute %s.'), Table::class);
        }
    }

    /**
     * @param array $data
     * @return int Last inserted ID.
     */
    public function insert(array $data): int
    {
        $query = new InsertStatement($this->table, $data);

        if ($this->adapter->execute($query))
        {
            return $this->adapter->getLastInsertId();
        }

        return 0;
    }

    /**
     * @param array $data
     * @param array $conditions
     * @return bool
     */
    public function update(array $data, array $conditions = []): bool
    {
        $query = new UpdateStatement($this->table, $data, $conditions);
        return $this->adapter->execute($query);
    }

    /**
     * @param array $conditions
     * @return bool
     */
    public function delete(array $conditions = []): bool
    {
        $query = new DeleteStatement($this->table, $conditions);
        return $this->adapter->execute($query);
    }

    /**
     * @param array $conditions
     * @return int
     */
    public function count(array $conditions = []): int
    {
        $query = $this
            ->select(['count' => new Expression('COUNT(*)')])
            ->where($conditions);

        return $this->getAdapter()->fetchOne($query);
    }

    /**
     * @param array $conditions
     * @return array
     */
    public function getOne(array $conditions): array
    {
        $query = $this->select()->where($conditions)->limit(1);
        return $this->getAdapter()->fetchRow($query);
    }

    /**
     * @param array $conditions
     * @param int|null $limit
     * @return array
     */
    public function getMany(array $conditions = [], ?int $limit = null): array
    {
        $query = $this->select()->where($conditions);

        if ($limit !== null)
        {
            $query->limit($limit);
        }

        return $this->getAdapter()->fetchAll($query);
    }

    /**
     * @param array $conditions
     * @return object|null
     */
    public function getModel(array $conditions): ?object
    {
        $row = $this->getOne($conditions);
        return $this->table->getModel($row);
    }

    /**
     * @param array $conditions
     * @param int|null $limit
     * @return object[]
     */
    public function getModels(array $conditions = [], ?int $limit = null): array
    {
        $rows = $this->getMany($conditions, $limit);
        $models = [];

        foreach ($rows as $row)
        {
            $models[] = $this->table->getModel($row);
        }

        return $models;
    }

    /**
     * @param string $class
     * @return Repository
     */
    protected function getRepository(string $class): Repository
    {
        if (!$this->container->has($class))
        {
            $this->container[$class] = new $class($this->adapter, $this->container);
        }

        return $this->container[$class];
    }

    /**
     * @return AdapterInterface
     */
    protected function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    /**
     * @param array $columns
     * @return SelectStatement
     */
    protected function select(array $columns = []): SelectStatement
    {
        return new SelectStatement($this->table, $columns);
    }

    /**
     * @param string $tableName
     * @param string|null $tableAlias
     * @param array $columns
     * @return SelectStatement
     */
    protected function selectFrom(string $tableName, ?string $tableAlias = null, array $columns = []): SelectStatement
    {
        $table = new Table($tableName, $tableAlias);
        return new SelectStatement($table, $columns);
    }

    /**
     * @param string[] $queries
     * @param string|null $type
     * @return string
     */
    #[Pure]
    protected function union(array $queries, ?string $type = null): string
    {
        return new Union($queries, $type);
    }
}