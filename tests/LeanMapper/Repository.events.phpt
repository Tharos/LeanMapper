<?php

use LeanMapper\Connection;
use LeanMapper\Entity;
use LeanMapper\IEntityFactory;
use LeanMapper\IMapper;
use LeanMapper\Repository;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

////////////////////

$log = new ArrayObject;

/**
 * @table author
 */
class CustomRepository extends Repository
{

    private $log;



    public function __construct(Connection $connection, IMapper $mapper, IEntityFactory $entityFactory, ArrayObject $log)
    {
        parent::__construct($connection, $mapper, $entityFactory);
        $this->log = $log;
    }



    protected function initEvents()
    {
        $this->onAfterPersist[] = function ($author) {
            $this->log->append('after persist: ' . $author->name);
        };
    }

}

$repository = new CustomRepository($connection, $mapper, $entityFactory, $log);

$repository->onBeforePersist[] = function ($author) use ($log) {
    $log->append('before persist: ' . $author->name);
};

$repository->onBeforeCreate[] = function ($author) use ($log) {
    $log->append('before create: ' . $author->name);
};

/**
 * @property int $id
 * @property string $name
 */
class Author extends Entity
{
}

////////////////////

$author = new Author;

$author->name = 'John Doe';

$repository->persist($author);

$repository->delete($author);

Assert::equal(
    [
        'before persist: John Doe',
        'before create: John Doe',
        'after persist: John Doe',
    ],
    $log->getArrayCopy()
);
