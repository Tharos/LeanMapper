<?php

use Tester\Assert;

use PDO as DbLayer;

require_once __DIR__ . '/../bootstrap.php';

//////////

/**
 * @property int $id
 * @property string $stateSelf m:enum(self::STATE_*)
 * @property string $stateStatic m:enum(static::STATE_*)
 * @property int $stateUse m:enum(DbLayer::ATTR_*)
 * @property int $type m:enum(self::TYPE_*)
 */
class Author extends LeanMapper\Entity
{

	const STATE_ACTIVE = 'active';

	const STATE_INACTIVE = 'inactive';

	const STATE_DELETED = 'deleted';

	const TYPE_STANDARD = 0;

	const TYPE_EXTRA = 1;

}

/**
 * @property string $stateParent m:enum(parent::STATE_*)
 */
class ExtraAuthor extends Author
{

	const STATE_ACTIVE = 'superactive';

}

//////////

$author = new Author;

$author->stateSelf = Author::STATE_ACTIVE;

Assert::equal(Author::STATE_ACTIVE, $author->stateSelf);

$author->stateStatic = Author::STATE_INACTIVE;

Assert::equal(Author::STATE_INACTIVE, $author->stateStatic);

//////////

$extraAuthor = new ExtraAuthor;

$extraAuthor->stateParent = Author::STATE_INACTIVE;

Assert::equal(Author::STATE_INACTIVE, $extraAuthor->stateParent);

Assert::exception(function () use ($extraAuthor) {
	$extraAuthor->stateParent = ExtraAuthor::STATE_ACTIVE;
}, 'LeanMapper\Exception\InvalidValueException', "Given value is not from possible values enumeration in property 'stateParent' in entity ExtraAuthor.");


Assert::exception(function () use ($extraAuthor) {
	$extraAuthor->stateStatic = ExtraAuthor::STATE_ACTIVE;
}, 'LeanMapper\Exception\InvalidValueException', "Given value is not from possible values enumeration in property 'stateStatic' in entity ExtraAuthor.");

//////////

$author->type = 0;

Assert::equal(0, $author->type);

$author->type = Author::TYPE_STANDARD;

Assert::equal(Author::TYPE_STANDARD, $author->type);

$author->type = ExtraAuthor::TYPE_EXTRA;

Assert::equal(ExtraAuthor::TYPE_EXTRA, $author->type);

//////////

$author->stateUse = DbLayer::ATTR_CASE; // usage of use statement

Assert::equal(PDO::ATTR_CASE, $author->stateUse);