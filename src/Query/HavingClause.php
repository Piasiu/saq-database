<?php
namespace Saq\Database\Query;

use JetBrains\PhpStorm\Pure;

class HavingClause extends ConditionCollection
{
    /**
     * @inheritDoc
     */
    #[Pure]
    public function get(): string
    {
        if ($this->exists())
        {
            return ' HAVING '.parent::get();
        }

        return '';
    }
}