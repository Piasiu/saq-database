<?php
namespace Saq\Database\Query;

use JetBrains\PhpStorm\Pure;

abstract class Value
{
    /**
     * @param mixed $value
     * @return string
     */
    public function prepareValue(mixed $value): string
    {
        if ($value instanceof Expression)
        {
            return $value;
        }

        if (is_int($value))
        {
            return (string)$value;
        }

        if (is_bool($value))
        {
            return $value ? '1' : '0';
        }

        if (is_null($value))
        {
            return 'NULL';
        }

        if (is_array($value))
        {
            $parts = [];

            foreach ($value as $data)
            {
                $parts[] = sprintf('"%s"', $this->escapeString($data));
            }

            return implode(', ', $parts);
        }

        return sprintf('"%s"', $this->escapeString($value));
    }

    /**
     * @param string $string
     * @return string
     */
    private function escapeString(string $string): string
    {
        $search = ["\\", "\x00", "\n", "\r", "'", '"', "\x1a"];
        $replace = ["\\\\", "\\0", "\\n", "\\r", "\'", '\"', "\\Z"];
        return str_replace($search, $replace, $string);
    }
}