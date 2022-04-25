<?php
namespace Saq\Database\Adapter;

class SQLiteAdapter extends PDOAdapter
{
    public const MEMORY_MODE = ':memory:';

    /**
     * @param string $path
     */
    public function __construct(string $path = self::MEMORY_MODE)
    {
        parent::__construct('sqlite:'.$path);
    }
}