<?php
namespace Saq\Database;

interface AdapterInterface
{
    /**
     * @param string $query
     * @return bool
     */
    function execute(string $query): bool;

    /**
     * @param string $query
     * @return array
     */
    function fetchRow(string $query): array;

    /**
     * @param string $query
     * @return array
     */
    function fetchAll(string $query): array;

    /**
     * @param string $query
     * @param int $columnIndex
     * @return mixed
     */
    function fetchOne(string $query, int $columnIndex = 0): mixed;

    /**
     * @param string $query
     * @param int $columnIndex
     * @return array
     */
    function fetchColumn(string $query, int $columnIndex = 0): array;

    /**
     * @return int
     */
    function getLastInsertId(): int;

    /**
     * @return bool
     */
    function beginTransaction(): bool;

    /**
     * @return bool
     */
    function commit(): bool;

    /**
     * @return bool
     */
    function rollBack(): bool;
}