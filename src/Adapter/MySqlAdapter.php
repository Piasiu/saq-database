<?php
namespace Saq\Database\Adapter;

class MySqlAdapter extends PDOAdapter
{
    /**
     * @param string $name
     * @param string $host
     * @param int $port
     * @param string|null $username
     * @param string|null $password
     * @param string $charset
     */
    public function __construct(string $name, string $host = 'localhost', int $port = 3306,
                                ?string $username = null, ?string $password = null, string $charset = 'utf8')
    {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s', $host, $port, $name);
        parent::__construct($dsn, $username, $password);
        $this->connection->exec('SET NAMES '.$charset);
    }
}