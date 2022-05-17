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
    private static ?string $cachePath = null;

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
    private array $otherData = [];

    /**
     * @param array $data
     */
    #[NoReturn]
    public function __construct(array $data = [])
    {
        $this->prepareProperties();
        $this->setProperties($data);
    }

    /**
     * @param array $data
     */
    public function setProperties(array $data): void
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
            $this->setComplexProperty($parts[0], $parts[1], $value);
        }
        else
        {
            $name = $this->mapToPropertyName($name);

            if (isset($this->properties[$name]))
            {
                $property = $this->properties[$name];

                if ($property->isList() && !is_array($value))
                {
                    $value = explode(',', $value);
                }

                call_user_func([$this, $property->getSetter()], $value);
            }
            else
            {
                $this->setOther($name, $value);
            }
        }
    }

    /**
     * @param string $name
     * @param string $subName
     * @param mixed $value
     */
    private function setComplexProperty(string $name, string $subName, mixed $value): void
    {
        $name = $this->mapToPropertyName($name);

        if (isset($this->properties[$name]))
        {
            $property = $this->properties[$name];
            $model = call_user_func([$this, $property->getGetter()]);

            if ($model === null)
            {
                $class = $property->getModel();
                /** @var Model $model */
                $model = new $class();
            }

            $model->setProperty($subName, $value);
            call_user_func([$this, $property->getSetter()], $model);
        }
        elseif (strlen($subName) > 0)
        {
            $data = $this->getOther($name, []);

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

            $this->setOther($name, $data);
        }
        else
        {
            $this->setOther($name, $value);
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

        foreach ($this->properties as $name => $property)
        {
            $value = call_user_func([$this, $property->getGetter()]);

            if ($value !== null)
            {
                if ($withDbNames)
                {
                    $name = $property->getDbName();
                }

                if ($property->isModel())
                {
                    $data[$name] = $value->asArray($withDbNames, $withExtraData);
                }
                else
                {
                    $data[$name] = $value;
                }
            }
        }

        if ($withExtraData)
        {
            return array_merge($data, $this->getOthers());
        }

        return $data;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setOther(string $name, mixed $value): void
    {
        $this->otherData[$name] = $value;
    }

    /**
     * @param string $name
     * @return bool
     */
    #[Pure]
    public function hasOther(string $name): bool
    {
        return array_key_exists($name, $this->otherData);
    }

    /**
     * @param string $name
     * @param mixed|null $default
     * @return mixed
     */
    #[Pure]
    public function getOther(string $name, mixed $default = null): mixed
    {
        return $this->hasOther($name) ? $this->otherData[$name] : $default;
    }

    /**
     * @return array
     */
    public function getOthers(): array
    {
        return $this->otherData;
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
        if (self::$cachePath === null)
        {
            $this->prepareFromModel();
        }
        elseif (!file_exists($this->getCacheFilePath()))
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

                    if ($property->isList())
                    {
                        call_user_func([$this, $property->getSetter()], []);
                    }
                    else
                    {
                        call_user_func([$this, $property->getSetter()], null);
                    }
                }
            }
        }
        catch (ReflectionException) { }
    }

    private function saveCache(): void
    {
        $data = [
            $this->map,
            []
        ];

        foreach ($this->properties as $name => $property)
        {
            $data[1][$name] = [$property->getDbName(), $property->getModel()];
        }

        $json = json_encode($data);
        file_put_contents($this->getCacheFilePath(), $json);
    }

    private function prepareFromCache(): void
    {
        $json = file_get_contents($this->getCacheFilePath());
        $data = json_decode($json, true);
        $this->map = $data[0];

        foreach ($data[1] as $name => $item)
        {
            $property = new Property($item[0], $item[1]);
            $property->setMethods($name);
            $this->properties[$name] = $property;
            call_user_func([$this, $property->getSetter()], null);
        }
    }

    /**
     * @return string
     */
    private function getCacheFilePath(): string
    {
        $name = str_replace('\\', '-', get_class($this));
        return self::$cachePath.$name.'.json';
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

    /**
     * @param string $path
     */
    public static function setCache(string $path): void
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR);

        if (!file_exists($path))
        {
            mkdir($path, 0777, true);
        }

        self::$cachePath = $path.DIRECTORY_SEPARATOR;
    }
}