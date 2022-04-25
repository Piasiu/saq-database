<?php
namespace Saq\Database\Query;

use JetBrains\PhpStorm\Pure;

class Union implements Stringable
{
    public const ALL       = 'ALL';
    public const DISTINCT  = 'DISTINCT';

    /**
     * @var string[]
     */
    private array $queries;

    /**
     * @var string
     */
    private string $clause = 'UNION';

    /**
     * @param string[] $queries
     * @param string|null $type
     */
    public function __construct(array $queries, ?string $type = null)
    {
        $this->queries = $queries;

        if ($type !== null)
        {
            $this->clause = 'UNION '.$type;
        }
    }

    /**
     * @return string
     */
    #[Pure]
    public function __toString(): string
    {
        return '('.implode(') '.$this->clause.' (', $this->queries).')';
    }
}