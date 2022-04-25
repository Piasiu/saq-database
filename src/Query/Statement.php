<?php
namespace Saq\Database\Query;

use Saq\Database\Table;
use Stringable;

abstract class Statement extends Value implements Stringable
{
    /**
     * @var Table
     */
    protected Table $table;

    /**
     * @param Table $table
     */
    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    /**
     * @return string
     */
    public abstract function getQuery(): string;

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getQuery();
    }
}