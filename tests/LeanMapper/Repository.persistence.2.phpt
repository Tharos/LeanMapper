<?php

declare(strict_types=1);

use LeanMapper\Entity;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

class TestUuid
{
    private string $bytes;

    public function __construct(string $bytes)
    {
        $this->bytes = $bytes;
    }

    public function getBytes(): string
    {
        return $this->bytes;
    }
}

/**
 * @property TestUuid $id
 * @property string $name
 */
class Session extends Entity
{
    public function getId(): TestUuid
    {
        return new TestUuid($this->row->id);
    }

    public function setId(TestUuid $uuid): string
    {
        return $this->row->id = $uuid->getBytes();
    }
}

class SessionRepository extends \LeanMapper\Repository
{
    public function find($id): Session
    {
        $row = $this->connection->select('*')->from($this->getTable())->where('id = ?', $id)->fetch();
        if ($row === false) {
            throw new \Exception('Entity was not found.');
        }

        /** @var Session */
        return $this->createEntity($row);
    }
}

$connection = Tests::createConnection();
$mapper = Tests::createMapper();
$entityFactory = Tests::createEntityFactory();

$connection->query('CREATE TABLE IF NOT EXISTS [session] ([id] TEXT PRIMARY KEY, [name] TEXT)');
$connection->query('DELETE FROM [session]');
$connection->query('INSERT INTO [session] ([id], [name]) VALUES ("binary_uuid_bytes_1", "Old Name")');
$connection->query('INSERT INTO [session] ([id], [name]) VALUES ("binary_uuid_bytes_2", "To Delete")');

$sessionRepository = new SessionRepository($connection, $mapper, $entityFactory);

test('Repository update uses raw row data instead of decoded object for ID', static function () use ($sessionRepository, $connection) {
    $session = $sessionRepository->find('binary_uuid_bytes_1');

    Assert::type(TestUuid::class, $session->id);
    Assert::same('binary_uuid_bytes_1', $session->id->getBytes());

    $session->name = 'New Name';

    Assert::noError(static function () use ($sessionRepository, $session) {
        $sessionRepository->persist($session);
    });

    $updatedName = $connection->query('SELECT [name] FROM [session] WHERE [id] = ?', 'binary_uuid_bytes_1')->fetchSingle();
    Assert::same('New Name', $updatedName);
});

test('Repository delete uses raw row data instead of decoded object for ID', static function () use ($sessionRepository, $connection) {
    $session = $sessionRepository->find('binary_uuid_bytes_2');

    Assert::noError(static function () use ($sessionRepository, $session) {
        $sessionRepository->delete($session);
    });

    $count = $connection->query('SELECT COUNT(*) FROM [session] WHERE [id] = ?', 'binary_uuid_bytes_2')->fetchSingle();
    Assert::same(0, $count);
});