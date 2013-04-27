<?php

namespace Model\Entity;

use Model\Entity\Application as Appl;

/**
 * @author Vojtěch Kohout
 *
 * @property-read int $id
 * @property string $name
 * @property bool|null $secret
 * @property Appl $application m:hasOne
 * @property \Model\Reader[]|null $readers m:hasMany(:reader_book)
 * @property \Model\Author[]|null $authors m:belongsToMany
 */
class Book extends \LeanMapper\Entity
{
}
