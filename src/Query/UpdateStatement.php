<?php
namespace Saq\Database\Query;

use Saq\Database\Table;

class UpdateStatement extends Statement
{
    /**
     * @var array
     */
    private array $data;

    /**
     * @var WhereClause
     */
    private WhereClause $whereClause;

    /**
     * @param Table $table
     * @param array $data
     * @param array $conditions
     */
    public function __construct(Table $table, array $data, array $conditions = [])
    {
        parent::__construct($table);
        $this->data = $data;
        $this->whereClause = new WhereClause($conditions);
    }

    /**
     * @inheritDoc
     */
    public function getQuery(): string
    {
        return sprintf('UPDATE %s SET %s%s', $this->table->getName(), $this->getDataPart(), $this->whereClause->get());
    }

    /**
     * @return string
     */
    private function getDataPart(): string
    {
        $result = [];

        foreach ($this->data as $name => $value)
        {
            $result[] = sprintf('`%s` = %s', $name, $this->prepareValue($value));
        }

        return implode(', ', $result);
    }
}