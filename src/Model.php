<?php
namespace Saq\Database;

use JetBrains\PhpStorm\NoReturn;
use JetBrains\PhpStorm\Pure;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use RuntimeException;

class Model
{
    /**
     * Model name separator
     * @var string
     */
    public const NAME_SEPARATOR = '$';

    /**
     * Model name separator
     * @var string
     */
    public const ARRAY_IDENTIFIER = '_';

    /**
     * Path to cache folder
     * @var string|null
     */
    public static ?string $cache = null;

    /**
     * @var Property[]
     */
    private array $properties = [];

    /**
     * @var string[]
     */
    private array $map = [];

    /**
     * @var array
     */
    private array $extraData = [];

    /**
     * @param array $data
     */
    #[NoReturn]
    public function __construct(array $data = [])
    {
        $this->prepareProperties();
        $this->setData($data);
    }

    /**
     * @param array $data
     */
    public function setData(array $data): void
    {
        foreach ($data as $name => $value)
        {
            $this->setProperty($name, $value);
        }
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setProperty(string $name, mixed $value): void
    {
        $parts = explode(self::NAME_SEPARATOR, $name, 2);

        if (isset($parts[1]))
        {
            $this->setModelProperty($parts[0], $parts[1], $value);
        }
        else
        {
            $name = $this->mapToPropertyName($name);

            if (isset($this->properties[$name]))
            {
                $property = $this->properties[$name];
                $property->setInitialized(true);
                call_user_func([$this, $property->getSetter()], $value);
            }
            else
            {
                $this->setExtraData($name, $value);
            }
        }
    }

    /**
     * @param string $name
     * @param string $subName
     * @param mixed $value
     */
    private function setModelProperty(string $name, string $subName, mixed $value): void
    {
        $name = $this->mapToPropertyName($name);

        if (isset($this->properties[$name]))
        {
            $property = $this->properties[$name];
            $model = call_user_func([$this, $property->getGetter()]);

            if ($model === null)
            {
                $property->setInitialized(true);
                $class = $property->getModel();
                /** @var Model $model */
                $model = new $class();
            }

            $model->setProperty($subName, $value);
            call_user_func([$this, $property->getSetter()], $model);
        }
        elseif (strlen($subName) > 0)
        {
            $data = $this->getExtraData($name, []);

            if ($subName[0] === self::ARRAY_IDENTIFIER)
            {
                if (strlen($subName) > 1)
                {
                    $subName = ltrim($subName, self::ARRAY_IDENTIFIER);
                    $data[$subName] = explode(',', $value);
                }
                else
                {
                    $data = array_merge($data, explode(',', $value));
                }
            }
            else
            {
                $data[$subName] = $value;
            }

            $this->setExtraData($name, $data);
        }
        else
        {
            $this->setExtraData($name, $value);
        }
    }

    /**
     * @param bool $withDbNames
     * @param bool $withExtraData
     * @return array
     */
    public function asArray(bool $withDbNames = false, bool $withExtraData = false): array
    {
        $data = [];
        $rc = new ReflectionClass($this);

        foreach ($this->properties as $name => $property)
        {
            if (!$property->isInitialized())
            {
                try
                {
                    $rp = $rc->getProperty($name);
                    $property->setInitialized($rp->isInitialized($this));
                }
                catch (ReflectionException)
                {
                }
            }

            if ($property->isInitialized())
            {
                $value = call_user_func([$this, $property->getGetter()]);

                if ($withDbNames)
                {
                    $name = $property->getDbName();
                }

                if ($property->isModel())
                {
                    $data[$name] = $value->asArray();
                }
                else
                {
                    $data[$name] = $value;
                }
            }
        }

        if ($withExtraData)
        {
            return array_merge($data, $this->extraData);
        }

        return $data;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setExtraData(string $name, mixed $value): void
    {
        $this->extraData[$name] = $value;
    }

    /**
     * @param string $name
     * @return bool
     */
    #[Pure]
    public function hasExtraData(string $name): bool
    {
        return array_key_exists($name, $this->extraData);
    }

    /**
     * @param string $name
     * @param mixed|null $default
     * @return mixed
     */
    #[Pure]
    public function getExtraData(string $name, mixed $default = null): mixed
    {
        return $this->hasExtraData($name) ? $this->extraData[$name] : $default;
    }

    /**
     * @return array
     */
    public function getAllExtraData(): array
    {
        return $this->extraData;
    }

    /**
     * @param string $name
     * @return string
     */
    public function mapToPropertyName(string $name): string
    {
        if (isset($this->map[$name]))
        {
            return $this->map[$name];
        }

        return $name;
    }

    /**
     * @param string $name
     * @return string
     */
    #[Pure]
    public function mapToDbName(string $name): string
    {
        if (isset($this->properties[$name]) && $this->properties[$name]->getDbName() !== null)
        {
            return $this->properties[$name]->getDbName();
        }

        return $name;
    }

    private function prepareProperties(): void
    {
        if (self::$cache === null)
        {
            $this->prepareFromModel();
        }
        elseif (!file_exists(self::$cache))
        {
            $this->prepareFromModel();
            $this->saveCache();
        }
        else
        {
            $this->prepareFromCache();
        }
    }

    private function prepareFromModel(): void
    {
        try
        {
            $reflectionClass = new ReflectionClass($this);
            $reflectionPropertyList = $reflectionClass->getProperties(ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PROTECTED);

            foreach ($reflectionPropertyList as $reflectionProperty)
            {
                $paList = $reflectionProperty->getAttributes(Property::class);
                $plaList = $reflectionProperty->getAttributes(PropertyList::class);

                if (count($paList) > 0)
                {
                    /** @var Property $property */
                    $property = $paList[0]->newInstance();
                    $name = $reflectionProperty->getName();
                    $property->setMethods($name);

                    if (!$reflectionClass->hasMethod($property->getSetter()))
                    {
                        throw new RuntimeException("Class {$reflectionClass->getName()} does not have method {$property->getSetter()}");
                    }

                    if (!$reflectionClass->hasMethod($property->getGetter()))
                    {
                        throw new RuntimeException("Class {$reflectionClass->getName()} does not have method {$property->getGetter()}");
                    }

                    if ($property->getDbName() !== null)
                    {
                        $this->map[$property->getDbName()] = $name;
                    }

                    $this->properties[$name] = $property;
                    call_user_func([$this, $property->getSetter()], null);
                }
            }
        }
        catch (ReflectionException)
        {
        }
    }

    private function saveCache(): void
    {
        $data = [
            'map' => $this->map,
            'properties' => []
        ];

        foreach ($this->properties as $name => $property)
        {
            $data['properties'][$name] = [$property->getDbName(), $property->getModel()];
        }

        $json = json_encode($data);
        file_put_contents(self::$cache, $json);
    }

    private function prepareFromCache(): void
    {
        $json = file_get_contents(self::$cache);
        $data = json_decode($json, true);
        $this->map = $data['map'];

        foreach ($data['properties'] as $name => $item)
        {
            $property = new Property($item[0], $item[1]);
            $property->setMethods($name);
            $this->properties[$name] = $property;
            call_user_func([$this, $property->getSetter()], null);
        }
    }

    /**
     * @param string $name
     * @param string $subName
     * @return string
     */
    public static function sub(string $name, string $subName): string
    {
        return $name.self::NAME_SEPARATOR.$subName;
    }

    /**
     * @param string $name
     * @param string|null $subName
     * @return string
     */
    public static function arr(string $name, ?string $subName = null): string
    {
        $name .= self::NAME_SEPARATOR.self::ARRAY_IDENTIFIER;
        $name .= $subName ?? '';
        return $name;
    }
}