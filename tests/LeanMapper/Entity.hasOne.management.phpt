<?php

declare(strict_types=1);

use LeanMapper\Entity;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

/**
 * @property int $id
 * @property string $name
 */
class Author extends Entity {}

/**
 * @property int $id
 * @property string $name
 * @property Author|null $author m:hasOne(author_id)
 */
class Book extends Entity {}

/**
 * @property int $id
 * @property string $content
 * @property Book $book m:hasOne(book_id)
 */
class Review extends Entity {}

class ReviewRepository extends \LeanMapper\Repository
{
    public function find($id): Review
    {
        $row = $this->connection->select('*')->from($this->getTable())->where('id = %i', $id)->fetch();
        if ($row === false) {
            throw new \Exception('Entity was not found.');
        }
		/** @var Review */
        return $this->createEntity($row);
    }
}

class BookRepository extends \LeanMapper\Repository
{
    public function find($id): Book
    {
        $row = $this->connection->select('*')->from($this->getTable())->where('id = %i', $id)->fetch();
        if ($row === false) {
            throw new \Exception('Entity was not found.');
        }
		/** @var Book */
        return $this->createEntity($row);
    }
}

class AuthorRepository extends \LeanMapper\Repository
{
    public function find($id): Author
    {
        $row = $this->connection->select('*')->from($this->getTable())->where('id = %i', $id)->fetch();
        if ($row === false) {
            throw new \Exception('Entity was not found.');
        }
		/** @var Author */
        return $this->createEntity($row);
    }
}

$connection = Tests::createConnection();
$mapper = Tests::createMapper();
$entityFactory = Tests::createEntityFactory();

$connection->query('CREATE TABLE IF NOT EXISTS [review] ([id] INTEGER PRIMARY KEY, [content] TEXT, [book_id] INTEGER)');
$connection->query('DELETE FROM [review]');
$connection->query('INSERT INTO [review] ([id], [content], [book_id]) VALUES (1, "Great book!", 1)');
$connection->query('INSERT INTO [review] ([id], [content], [book_id]) VALUES (2, "Another book", 2)');

$reviewRepository = new ReviewRepository($connection, $mapper, $entityFactory);
$bookRepository = new BookRepository($connection, $mapper, $entityFactory);
$authorRepository = new AuthorRepository($connection, $mapper, $entityFactory);

test('HasOne relationship cache remains stale without persist', static function () use ($reviewRepository, $authorRepository) {
    $review = $reviewRepository->find(2);
    $book = $review->book;

    $originalAuthor = $book->author;
    Assert::same(2, $originalAuthor->id, 'Reading the relation populates the shared Result object\'s $referenced array.');

    $book->name = 'Mutated without persist';

    $newAuthor = $authorRepository->find(1);
    $book->author = $newAuthor;

    $reloadedBook = $review->book;

    Assert::same('Mutated without persist', $reloadedBook->name, 'Scalar properties share the Result $data array and sync across wrappers even without persist().');
    Assert::null($reloadedBook->author, 'The new entity wrapper hits the stale $referenced array in the shared Result object, which lacks the new foreign key and returns null.');

    Assert::notNull($book->author, 'The original entity instance retains the unpersisted relation in its Row\'s $referencedRows property.');
    Assert::same(1, $book->author->id, 'The original entity instance still points to the unpersisted author ID.');
});

test('HasOne relationship cache invalidation with persist', static function () use ($reviewRepository, $bookRepository, $authorRepository) {
    $review = $reviewRepository->find(1);
    $book = $review->book;

    $originalAuthor = $book->author;
    Assert::same(1, $originalAuthor->id, 'Reading the relation populates the shared Result object\'s $referenced array.');

    $book->name = 'Mutated with persist';

    $newAuthor = $authorRepository->find(2);
    $book->author = $newAuthor;

    $bookRepository->persist($book);

    $reloadedBook = $review->book;

    Assert::same('Mutated with persist', $reloadedBook->name, 'Scalar properties are updated in the shared Result $data array and reflect immediately across wrappers.');

    Assert::notNull($reloadedBook->author, 'The new entity wrapper should query the database since markAsUpdated() cleared the shared Result\'s $referenced array.');
    Assert::same(2, $reloadedBook->author->id, 'The new entity wrapper should reflect the persisted author ID.');
    Assert::same($newAuthor->name, $reloadedBook->author->name, 'The new entity wrapper should reflect the persisted author name.');
});
