<?php

declare(strict_types=1);

namespace LeanMapper;

class EntityDataDecoder
{

    /**
     * @return mixed|null
     */
    public static function process(Entity $entity, int $maxDepth = 10)
    {
        if ($maxDepth <= 0) {
            if (!method_exists($entity, '_getMapper')) {
                throw new \LeanMapper\Exception\Exception('Entity must have method _getMapper().');
            }
            $mapper = $entity->_getMapper();
            $primaryKey = $mapper->getPrimaryKey($mapper->getTable(get_class($entity)));
            return (isset($primaryKey) && isset($entity->$primaryKey)) ? $entity->$primaryKey : null;
        }
        $data = [];
        foreach ($entity->getData() as $name => $value) {
            if (is_array($value)) {
                $items = [];
                foreach ($value as $item) {
                    $items[] = self::process($item, $maxDepth - 1);
                }
                $value = $items;
            } elseif (is_object($value)) {
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
