<?php
namespace Saq\Database\Query;

use JetBrains\PhpStorm\Pure;

class ConditionCollection extends Value
{
    public const OPERATOR_AND = 'AND';
    public const OPERATOR_OR  = 'OR';

    /**
     * @var array
     */
    private array $conditions = [];

    /**
     * @param array $conditions
     */
    public function __construct(array $conditions = [])
    {
        $this->addMany($conditions);
    }

    /**
     * @param string $condition
     * @param mixed $value
     * @param string $operator
     */
    public function add(string $condition, mixed $value = null, string $operator = self::OPERATOR_AND): void
    {
        if ($value !== null)
        {
            $condition = str_replace('?', $this->prepareValue($value), $condition);
        }

        if (count($this->conditions) > 0)
        {
            $condition = $operator.' '.$condition;
        }

        $this->conditions[] = $condition;
    }

    /**
     * @param array $conditions
     */
    public function addMany(array $conditions = []): void
    {
        foreach ($conditions as $condition => $value)
        {
            $this->add($condition, $value);
        }
    }

    /**
     * @return bool
     */
    #[Pure]
    public function exists(): bool
    {
        return count($this->conditions) > 0;
    }

    /**
     * @return string
     */
    #[Pure]
    public function get(): string
    {
        return implode(' ', $this->conditions);
    }
}