<?php
namespace Saq\Database\Query;

use JetBrains\PhpStorm\Pure;
use Saq\Database\Table;

class InsertStatement extends Statement
{
    /**
     * @var array
     */
    private array $data;

    /**
     * @var array
     */
    private array $columns;

    /**
     * @param Table $table
     * @param array $data
     * @param array|null $columns
     */
    #[Pure]
    public function __construct(Table $table, array $data, ?array $columns = null)
    {
        parent::__construct($table);

        if ($columns === null)
        {
            $this->data[] = $data;
            $this->columns = array_keys($data);
        }
        else
        {
            $this->data = $data;
            $this->columns = $columns;
        }
    }

    /**
     * @inheritDoc
     */
    public function getQuery(): string
    {
        return sprintf('INSERT INTO %s(%s) VALUES%s', $this->table->getName(), $this->getColumnsPart(), $this->getDataPart());
    }

    /**
     * @return string
     */
    #[Pure]
    private function getColumnsPart(): string
    {
        return '`'.implode('`,`', $this->columns).'`';
    }

    /**
     * @return string
     */
    private function getDataPart(): string
    {
        $rows = [];

        foreach ($this->data as $row)
        {
            $rows[] = $this->prepareRow($row);
        }

        return implode(', ', $rows);
    }

    /**
     * @param array $row
     * @return string
     */
    private function prepareRow(array $row): string
    {
        $parts = [];

        foreach ($row as $value)
        {
            $parts[] = $this->prepareValue($value);
        }

        return '('.implode(', ', $parts).')';
    }
}