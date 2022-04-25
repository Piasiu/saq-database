<?php
namespace Saq\Database\Query;

use JetBrains\PhpStorm\Pure;

class WhereClause extends ConditionCollection
{
    /**
     * @inheritDoc
     */
    #[Pure]
    public function get(): string
    {
        if ($this->exists())
        {
            return ' WHERE '.parent::get();
        }

        return '';
    }
}