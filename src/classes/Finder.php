<?php

namespace classes;

use classes\data\Adapter;
use classes\entity\Entity;

class Finder
{
    /** @var Adapter $db */
    protected $db;

    protected $data = [];

    public function __construct(Adapter $db)
    {
        $this->db = $db;
    }

    /** @return Entity|bool */
    public function find($type, $id)
    {
        if ($id === null) {
            $id = 0;
        }
        if (is_numeric($id) && isset($this->data[$type][$id]))
        {
            return $this->data[$type][$id];
        }
        $class_type = 'classes\\entity\\' . $type;
        if (!class_exists($class_type)) {
            return false;
        }
        /** @var Entity $entity */
        if (is_numeric($id) && $id == 0)
        {
            $entity = new $class_type([strtolower($type) . '_id' => 0]);
        }
        else if (is_array($id))
        {
            $data = $id;
            $entity = new $class_type($data ? $data : [strtolower($type) . '_id' => 0]);
            $this->data[$type][$data[strtolower($type) . '_id']] = $entity;
            $entity->setRelations();
        }
        else
        {
            $type = implode('_', preg_split('/(?=[A-Z])/', $type, -1, PREG_SPLIT_NO_EMPTY));

            $query = 'select * from core_' . strtolower($type) . ' where ' . strtolower($type) . (!is_numeric($id) ? 'name = \'' . $id . '\'' : '_id = ' . $id) . ' limit 1';
            $statement = $this->db->query($query);
            $data = $statement->fetch();

            $entity = new $class_type($data ? $data : [strtolower($type) . '_id' => 0]);

            $this->data[$type][$data[strtolower($type) . '_id'] ?? $id] = $entity;
            $this->data[$type][$data[strtolower($type) . 'name'] ?? $id] = $entity;

            $entity->setRelations();
        }
        return $entity;
    }
}
