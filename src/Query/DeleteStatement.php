<?php
namespace Saq\Database\Query;

use JetBrains\PhpStorm\Pure;
use Saq\Database\Table;

class DeleteStatement extends Statement
{
    /**
     * @var WhereClause
     */
    private WhereClause $whereClause;

    /**
     * @param Table $table
     * @param array $conditions
     */
    #[Pure]
    public function __construct(Table $table, array $conditions = [])
    {
        parent::__construct($table);
        $this->whereClause = new WhereClause($conditions);
    }

    /**
     * @inheritDoc
     */
    #[Pure]
    public function getQuery(): string
    {
        return 'DELETE FROM '.$this->table->getName().$this->whereClause->get();
    }
}