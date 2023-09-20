<?php

namespace classes\data;

class Statement
{
    /** @var Adapter */
    protected $adapter;
    protected $query;

    /** @var \mysqli_stmt */
    protected $statement;

    protected $fields;
    protected $keys = [];
    protected $values = [];

    public function __construct(Adapter $adapter, $query)
    {
        $this->adapter = $adapter;
        $this->query = $query;
    }

    public function execute()
    {
        $connection = $this->adapter->getConnection();

        if (!$this->statement) {
            $this->statement = $connection->prepare($this->query);
        }

        if (!$this->statement) {
            throw new SqlException('Statement error ' . $connection->connect_error . ': ' . $connection->error, $connection->errno, $connection->sqlstate);
        }

        $statement = $this->statement;

        $result = $statement->execute();

        if (!$result) {
            throw new SqlException('Query error ' . $connection->connect_error . ': ' . $connection->error, $connection->errno, $connection->sqlstate);
        }
        $meta = $statement->result_metadata();

        if ($meta) {
            $this->fields = $meta->fetch_fields();

            $statement->store_result();

            $keys = [];
            $values = [];
            $refs = [];
            $i = 0;

            foreach ($this->fields as $field) {
                $keys[] = $field->name;
                $refs[] = null;
                $values[] =& $refs[$i];

                $i++;
            }

            $this->keys = $keys;
            $this->values = $values;

            call_user_func_array([$statement, 'bind_result'], $this->values);
        }

        return true;
    }

    public function fetch() {
        $values = $this->fetchRowValues();
        if (!$values) {
            return false;
        }
        return array_combine($this->keys, $values);
    }

    public function fetchAll()
    {
        $output = [];
        while ($v = $this->fetch())
        {
            $output[] = $v;
        }

        return $output;
    }

    public function fetchColumn($key = 0)
    {
        $values = $this->fetchRowValues();
        if (!$values)
        {
            return false;
        }

        if (is_int($key))
        {
            return $values[$key] ?? null;
        }
        else
        {
            $values = array_combine($this->keys, $values);
            return $values[$key] ?? null;
        }
    }

    public function fetchAllColumn($key = 0)
    {
        $output = [];

        while (($v = $this->fetchColumn($key)) !== false)
        {
            $output[] = $v;
        }

        return $output;
    }

    public function fetchRowValues()
    {
        $statement = $this->statement;
        if (!$statement) {
            return false;
        }

        $success = $statement->fetch();

        if ($success === null) {
            return false;
        } else if ($success === false) {
            throw new SqlException("MySQL fetch error [$statement->errno]: $statement->error", $statement->errno, $statement->sqlstate);
        }

        $values = [];
        foreach ($this->values as $v) {
            $values[] = $v;
        }

        return $values;
    }

}