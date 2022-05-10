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
     * @var string|null
     */
    private ?string $model;

    /**
     * @param string $name
     * @param string|null $alias
     * @param string|null $model
     */
    public function __construct(string $name, ?string $alias = null, ?string $model = null)
    {
        $this->name = $name;
        $this->alias = $alias === null ? $name : $alias;
        $this->model = $model;
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

    /**
     * @return bool
     */
    public function hasModel(): bool
    {
        return $this->model !== null;
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function getModel(array $data): mixed
    {
        if ($this->hasModel())
        {
            return new $this->model($data);
        }

        return null;
    }
}