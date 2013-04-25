<?php

namespace Model\Entity;

use Model\Entity\Application;

/**
 * @author Vojtěch Kohout
 *
 * @property-read int $id
 * @property string $name
 * @property bool|null $secret
 * @property Application $application m:hasOne
 * @property \Model\Reader[]|null $reader m:hasMany(book_id:book_reader:reader_id:reader)
 */
class Book extends \LeanMapper\Entity
{
}
