<?php
namespace Saq\Database;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Table
{
    /**
     * @var string
     */
    private string $name;

    /**
     * @var string
     */
    private string $alias;

    /**
     * @param string $name
     * @param string|null $alias
     */
    public function __construct(string $name, ?string $alias = null)
    {
        $this->name = $name;
        $this->alias = $alias === null ? $name : $alias;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }
}