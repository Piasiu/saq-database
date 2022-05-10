<?php
namespace Saq\Database;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Property
{
    /**
     * @var string|null
     */
    private ?string $dbName;

    /**
     * @var bool
     */
    private bool $initialized = false;

    /**
     * @var string|null
     */
    private ?string $model;

    /**
     * @var bool
     */
    private bool $isList = false;

    /**
     * @var string|null
     */
    private ?string $setter = null;

    /**
     * @var string|null
     */
    private ?string $getter = null;

    /**
     * @param string|null $dbName
     * @param string|null $model
     * @param bool $isList
     */
    public function __construct(?string $dbName = null, ?string $model = null, bool $isList = false)
    {
        $this->dbName = $dbName;
        $this->model = $model;
        $this->isList = $isList;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setMethods(string $name): void
    {
        $preparedName = ucfirst($name);
        $this->setter = 'set'.$preparedName;
        $this->getter = 'get'.$preparedName;
    }

    /**
     * @return string|null
     */
    public function getDbName(): ?string
    {
        return $this->dbName;
    }

    /**
     * @return bool
     */
    public function isModel(): bool
    {
        return $this->model !== null;
    }

    /**
     * @return string|null
     */
    public function getModel(): ?string
    {
        return $this->model;
    }

    /**
     * @return bool
     */
    public function isList(): bool
    {
        return $this->isList;
    }

    /**
     * @return bool
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * @param bool $initialized
     * @return void
     */
    public function setInitialized(bool $initialized): void
    {
        $this->initialized = $initialized;
    }

    /**
     * @return string|null
     */
    public function getSetter(): ?string
    {
        return $this->setter;
    }

    /**
     * @return string|null
     */
    public function getGetter(): ?string
    {
        return $this->getter;
    }
}