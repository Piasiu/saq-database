<?php
namespace Saq\Database\Query;

use JetBrains\PhpStorm\Pure;
use RuntimeException;
use Saq\Database\Table;

class SelectStatement extends Statement
{
    public const INNER_JOIN = 'INNER';
    public const LEFT_JOIN  = 'LEFT';
    public const RIGHT_JOIN = 'RIGHT';

    public const ORDER_DIRECTION_ASC = 'ASC';
    public const ORDER_DIRECTION_DESC = 'DESC';

    /**
     * @var array
     */
    private array $columns = [];

    /**
     * @var array
     */
    private array $joins = [];

    /**
     * @var WhereClause
     */
    private WhereClause $whereClause;

    /**
     * @var array
     */
    private array $groups = [];

    /**
     * @var array
     */
    private array $orders = [];

    /**
     * @var string
     */
    private string $limit = '';

    /**
     * @var HavingClause
     */
    private HavingClause $havingClause;

    /**
     * @var string
     */
    private string $prefixSeparator;

    /**
     * @param Table $table
     * @param array $columns
     * @param string $prefixSeparator
     */
    public function __construct(Table $table, array $columns = [], string $prefixSeparator = '$')
    {
        parent::__construct($table);
        $this->columns($columns);
        $this->whereClause = new WhereClause();
        $this->havingClause = new HavingClause();
        $this->prefixSeparator = $prefixSeparator;
    }

    /**
     * @inheritDoc
     */
    public function getQuery(): string
    {
        $query = sprintf('SELECT %s FROM %s AS %s', $this->getColumnsPart(), $this->table->getName(), $this->table->getAlias());
        $query .= $this->getJoinPart();
        $query .= $this->whereClause->get();
        $query .= $this->getGroupPart();
        $query .= $this->havingClause->get();
        $query .= $this->getOrderPart();
        $query .= $this->getLimitPart();
        return $query;
    }

    /**
     * @param array $columns
     * @param array $prefixes
     * @return $this
     */
    public function columns(array $columns, array $prefixes = []): self
    {
        return $this->setColumns($columns, $this->table->getAlias(), $prefixes);
    }

    /**
     * @param string $tableName
     * @param string $tableAlias
     * @param string $onCondition
     * @param array $columns
     * @param array|string $prefixes
     * @param string $type
     * @return $this
     */
    public function join(string $tableName, string $tableAlias, string $onCondition,
                         array $columns = [], array|string $prefixes = [], string $type = self::INNER_JOIN): self
    {
        $this->joins[] = sprintf(' %s JOIN %s AS %s ON %s', $type, $tableName, $tableAlias, $onCondition);

        if (is_string($prefixes))
        {
            $prefixes = [$prefixes];
        }

        return $this->setColumns($columns, $tableAlias, $prefixes);
    }

    /**
     * @param string $tableName
     * @param string $tableAlias
     * @param string $onCondition
     * @param array $columns
     * @param array|string $prefixes
     * @return $this
     */
    public function leftJoin(string $tableName, string $tableAlias, string $onCondition,
                             array $columns = [], array|string $prefixes = []): self
    {
        return $this->join($tableName, $tableAlias, $onCondition, $columns, $prefixes, self::LEFT_JOIN);
    }

    /**
     * @param string $tableName
     * @param string $tableAlias
     * @param string $onCondition
     * @param array $columns
     * @param array|string $prefixes
     * @return $this
     */
    public function rightJoin(string $tableName, string $tableAlias, string $onCondition,
                             array $columns = [], array|string $prefixes = []): self
    {
        return $this->join($tableName, $tableAlias, $onCondition, $columns, $prefixes, self::RIGHT_JOIN);
    }

    /**
     * @param string|array $condition
     * @param mixed|null $value
     * @param string $operator
     * @return $this
     */
    public function where(string|array $condition, mixed $value = null, string $operator = ConditionCollection::OPERATOR_AND): self
    {
        if (is_string($condition))
        {
            $this->whereClause->add($condition, $value, $operator);
        }
        else
        {
            $this->whereClause->addMany($condition);
        }

        return $this;
    }

    /**
     * @param string|array $condition
     * @param mixed $value
     * @return $this
     */
    public function orWhere(string|array $condition, mixed $value = null): self
    {
        return $this->where($condition, $value, ConditionCollection::OPERATOR_OR);
    }

    /**
     * @param string $column
     * @return $this
     */
    public function group(string $column): self
    {
        $this->groups[] = $column;
        return $this;
    }

    /**
     * @param string|array $condition
     * @param mixed|null $value
     * @param string $operator
     * @return $this
     */
    public function having(string|array $condition, mixed $value = null, string $operator = ConditionCollection::OPERATOR_AND): self
    {
        if (is_string($condition))
        {
            $this->havingClause->add($condition, $value, $operator);
        }
        else
        {
            $this->havingClause->addMany($condition);
        }

        return $this;
    }

    /**
     * @param string|array $condition
     * @param mixed $value
     * @return $this
     */
    public function orHaving(string|array $condition, mixed $value = null): self
    {
        return $this->having($condition, $value, ConditionCollection::OPERATOR_OR);
    }

    /**
     * @param string $column
     * @param string $direction
     * @return $this
     */
    public function order(string $column, string $direction = self::ORDER_DIRECTION_ASC): self
    {
        $this->orders[] = $column.' '.$direction;
        return $this;
    }

    /**
     * @param int $max
     * @param int $offset
     * @return $this
     */
    public function limit(int $max, int $offset = 0): self
    {
        $this->limit = $offset.', '.$max;
        return $this;
    }

    /**
     * @param int $pageNo
     * @param int $perPage
     * @return $this
     */
    public function pagination(int $pageNo, int $perPage): self
    {
        $this->limit($perPage, ($pageNo - 1) * $perPage);
        return $this;
    }

    /**
     * @param array $columns
     * @param string $tableAlias
     * @param array $prefixes
     * @return $this
     */
    private function setColumns(array $columns, string $tableAlias, array $prefixes = []): self
    {
        $prefix = '';

        if (count($prefixes) > 0)
        {
            $prefix = implode($this->prefixSeparator, $prefixes).$this->prefixSeparator;
        }

        if (!isset($this->columns[$tableAlias]))
        {
            $this->columns[$tableAlias] = [];
        }

        foreach ($columns as $columnAlias => $column)
        {
            if (is_int($columnAlias))
            {
                if ($column instanceof Expression)
                {
                    throw new RuntimeException('Expression column requires an alias.');
                }

                $columnAlias = $column;
            }

            $this->columns[$tableAlias][$prefix.$columnAlias] = $column;
        }

        return $this;
    }

    /**
     * @return string
     */
    private function getColumnsPart(): string
    {
        $result = [];

        foreach ($this->columns as $tableAlias => $columns)
        {
            $tableColumns = [];

            foreach ($columns as $columnAlias => $column)
            {
                if ($column instanceof Expression)
                {
                    $tableColumns[] = $column.' AS '.$columnAlias;
                }
                else
                {
                    $tableColumns[] = $tableAlias.'.'.$column.' AS '.$columnAlias;
                }
            }

            if (count($tableColumns) > 0)
            {
                $result[] = implode(', ', $tableColumns);
            }
            elseif ($tableAlias === $this->table->getAlias())
            {
                $result[] = $tableAlias.'.*';
            }
        }

        return implode(', ', $result);
    }

    /**
     * @return string
     */
    #[Pure]
    private function getJoinPart(): string
    {
        return implode(' ', $this->joins);
    }

    /**
     * @return string
     */
    #[Pure]
    private function getGroupPart(): string
    {
        if (count($this->groups) > 0)
        {
            return ' GROUP BY '.implode(', ', $this->groups);
        }

        return '';
    }

    /**
     * @return string
     */
    #[Pure]
    private function getOrderPart(): string
    {
        if (count($this->orders) > 0)
        {
            return ' ORDER BY '.implode(', ', $this->orders);
        }

        return '';
    }

    /**
     * @return string
     */
    #[Pure]
    private function getLimitPart(): string
    {
        return strlen($this->limit) > 0 ? ' LIMIT '.$this->limit : '';
    }
}