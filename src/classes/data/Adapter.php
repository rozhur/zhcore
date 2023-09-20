<?php

namespace classes\data;

use mysqli;
use function mysqli_init;

class Adapter
{
    protected $config;

    /** @var mysqli */
    protected $connection;

    protected $queries = 0;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function query($query)
    {
        $this->connect();

        $statement = new Statement($this, $query);
        $statement->execute();
        $this->queries++;

        return $statement;
    }

    public function connect()
    {
        $this->getConnection();
        return true;
    }

    public function getConnection()
    {
        if (!$this->connection)
        {
            $this->connection = $this->makeConnection();
        }
        return $this->connection;
    }

    protected function makeConnection()
    {
        $config = $this->fixConfig($this->config);

        $connection = mysqli_init();

        $result = @$connection->real_connect($config['host'], $config['username'], $config['password'], $config['dbname'], $config['port'] ?: 3306);

        if ($result === false)
        {
            throw new SqlException('Error connection ' . $connection->connect_error);
        }

        return $connection;
    }

    protected function fixConfig($config)
    {
        if ($config == null) {
            $config = [];
        }
        return array_replace_recursive(['host' => 'localhost', 'password' => '', 'dbname' => 'core', 'port' => 3306], $config);
    }

    public function getQueries(): int
    {
        return $this->queries;
    }
}