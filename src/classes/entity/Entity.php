<?php

namespace classes\entity;

abstract class Entity
{
    public function __construct($data = []) {
        $class = new \ReflectionClass($this);
        foreach ($class->getProperties() as $property)
        {
            $p_name = $property->getName();
            if (preg_match('/^__/', $p_name)) {
                $p_name = substr($p_name, 2);
            }
            if (preg_match('/[A-Z]/', $p_name))
            {
                continue;
            }
            else if (isset($data[$p_name]))
            {
                $property->setValue($this, $data[$p_name]);
            }
        }
    }

    public function setRelations()
    {
        $class = new \ReflectionClass($this);
        foreach ($class->getProperties() as $property)
        {
            if ($property->getValue($this) instanceof Entity)
            {
                continue;
            }
            $p_name = $property->getName();
            if (preg_match('/^__/', $p_name))
            {
                $p_name = substr($p_name, 2);
            }
            if (preg_match('/[A-Z]/', $p_name))
            {
                $name = explode('_', $p_name);
                $name = end($name);
                $var = strtolower($p_name) . '_id';
                $id = $this->$var;
                if ($id)
                {
                    $value = \Core::app()->find($name, $id);
                }
                else
                {
                    $value = $data[$p_name] ?? null;
                }
                $property->setValue($this, $value);
            }
        }
    }

    public function decode() {
        $data = [];
        $class = new \ReflectionClass($this);
        $properties = $class->getProperties();
        foreach ($properties as $property)
        {
            if (preg_match('/^__/', $property->getName())) {
                continue;
            }
            $value = $property->getValue($this);
            if ($value && strpos($property->getName(), 'data')) {
                $value = date('d/m/Y H:i', strtotime($value));
            }
            $data[$property->getName()] = $value instanceof Entity ? $value->decode() : $value;
        }
        return $data;
    }

    public function save()
    {
        $class = new \ReflectionClass($this);
        $type = mb_split('\\\\', strtolower($class->getName()));
        $query = 'insert into core_' . $type[count($type) - 1] . ' values (';

        $connection = \Core::getDb()->getConnection();

        $properties = $class->getProperties();
        $size = count($properties);
        for ($i = 0; $i < $size; $i++)
        {
            $value = $properties[$i]->getValue($this) ?? 0;
            if ($value instanceof Entity)
            {
                continue;
            }
            if ($i > 0)
            {
                $query .= ',';
            }
            $value = is_bool($value) ? $value ? 1 : 0 : $value;
            $query .= is_numeric($value) ? $value : '\'' . $connection->escape_string($value) . '\'';
        }
        $query .= ') on duplicate key update ';

        for ($i = 0; $i < $size; $i++)
        {
            $field = $properties[$i]->getName();
            if (preg_match('/[A-Z]/', $field))
            {
                continue;
            }
            if ($i > 0)
            {
                $query .= ',';
            }
            if (preg_match('/^__/', $field))
            {
                $field = substr($field, 2);
            }
            $name = explode('_', $field);
            $name = end($name);
            if (preg_match('/[A-Z]/', $name))
            {
                $field = strtolower($field) . '_id';
            }
            $query .= $field . '=values(' . $field . ')';
        }
        echo $query;
        \Core::getDb()->query($query);
    }
}