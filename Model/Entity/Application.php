<?php

namespace Model\Entity;

/**
 * @author Vojtěch Kohout
 *
 * @property-read int $id
 * @property \DibiDateTime|null $released
 * @property Author $author m:hasOne
 * @property Author|null $maintainer m:hasOne(maintainer_id)
 * @property Tag[] $tags m:hasMany
 * @property string $title
 * @property string|null $web
 * @property string $slogan
 */
class Application extends \LeanMapper\Entity
{
}
