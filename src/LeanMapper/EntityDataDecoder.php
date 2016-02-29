<?php

namespace LeanMapper;

class EntityDataDecoder
{

    public static function process(Entity $entity, $maxDepth = 10)
    {

        if ($maxDepth <= 0) {
            $mapper = $entity->_getMapper();
            $primaryKey = $mapper->getPrimaryKey($mapper->getTable(get_class($entity)));
            return (isset($primaryKey) && isset($entity->$primaryKey)) ? $entity->$primaryKey: null;
        }
        $data = [];
        foreach ($entity->getData() as $name => $value) {
            if (is_array($value)) {
                $items = [];
                foreach ($value as $item) {
                    $items[] = self::process($item, $maxDepth - 1);
                }
                $value = $items;
            } elseif (is_object($value) && !is_null($value)) {
                if ($value instanceof \DateTime) {
                    $value = $value->format('Y-m-d H:i:s');
                } elseif ($value instanceof Entity) {
                    $value = self::process($value, $maxDepth - 1);
                }
            }
            $data[$name] = $value;
        }
        return $data;
    }
}
