<?php

declare(strict_types=1);

use LeanMapper\Connection;
use LeanMapper\Entity;
use LeanMapper\FilteringResult;
use LeanMapper\Fluent;
use LeanMapper\Result;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

// https://github.com/Tharos/LeanMapper/issues/65

$connection->query('CREATE TABLE `interval` (
  `id` unsinged int(10) NOT NULL,
  `left` int(11) NOT NULL,
  `right` int(11) NOT NULL,
  PRIMARY KEY (`id`)
)');

$connection->query('INSERT INTO `interval` (`id`, `left`, `right`) VALUES
(1, 10, 50),
(2, 5,  20),
(3, 30, 55),
(4, 60, 70),
(5, 20, 30),
(6, 20, 40)');

$connection->registerFilter('intersecingIntervals', function (Fluent $fluent) use ($connection, $mapper) {

    $relatedKeys = $fluent->getRelatedKeys();

    $data = $connection->query('
        SELECT [innerInt.*], [baseInt.id] [interval_id]
        FROM [interval] [baseInt]
        JOIN [interval] [innerInt] ON [innerInt.left] <= [baseInt.right] AND [innerInt.right] >= [baseInt.left] AND [baseInt.id] != [innerInt.id]
        WHERE [baseInt.id] IN %in
        ORDER BY [baseInt.id], [innerInt.id]
    ', $relatedKeys)->fetchAll();

    $referencing = $referenced = [];
    foreach ($data as $row) {
        $referencing[] = [
            'interval_id' => $row->id,
            'target_id' => $row->interval_id,
        ];
        if (!isset($referenced[$row->id])) {
            unset($row->interval_id);
            $referenced[$row->id] = (array) $row;
        }
    }

    $referencing = Result::createInstance($referencing, 'virtualHasMany', $connection, $mapper);
    $referenced = Result::createInstance($referenced, 'interval', $connection, $mapper);
    $referencing->setReferencedResult($referenced, 'interval', 'target_id');

    return new FilteringResult($referencing, function ($newRelatedKeys) use ($relatedKeys) {
        return $relatedKeys === $newRelatedKeys;
    });
});

////////////////////

/**
 * @property int $id
 * @property int $left
 * @property int $right
 * @property-read Interval[] $intersectingIntervals m:hasMany(::target_id) m:filter(intersecingIntervals)
 */
class Interval extends Entity
{
}


class IntervalRepository extends \LeanMapper\Repository
{

    /**
     * @return Interval[]
     */
    public function findAll()
    {
        return $this->createEntities(
            $this->createFluent()->fetchAll()
        );
    }
}

////////////////////

$intervalRepository = new IntervalRepository($connection, $mapper, $entityFactory);

$intervals = $intervalRepository->findAll();

$results = [];

foreach ($intervals as $interval) {
    $intersections = [];
    foreach ($interval->intersectingIntervals as $intersectInt) {
        $intersections[] = "$intersectInt->id ($intersectInt->left, $intersectInt->right)";
    }
    $results[] = [
        "Intersections for interval $interval->id ($interval->left, $interval->right)",
        $intersections,
    ];
}

Assert::same([
    [
        'Intersections for interval 1 (10, 50)',
        [
            '2 (5, 20)',
            '3 (30, 55)',
            '5 (20, 30)',
            '6 (20, 40)',
        ],
    ],
    [
        'Intersections for interval 2 (5, 20)',
        [
            '1 (10, 50)',
            '5 (20, 30)',
            '6 (20, 40)',
        ],
    ],
    [
        'Intersections for interval 3 (30, 55)',
        [
            '1 (10, 50)',
            '5 (20, 30)',
            '6 (20, 40)',
        ],
    ],
    [
        'Intersections for interval 4 (60, 70)',
        [],
    ],
    [
        'Intersections for interval 5 (20, 30)',
        [
            '1 (10, 50)',
            '2 (5, 20)',
            '3 (30, 55)',
            '6 (20, 40)',
        ],
    ],
    [
        'Intersections for interval 6 (20, 40)',
        [
            '1 (10, 50)',
            '2 (5, 20)',
            '3 (30, 55)',
            '5 (20, 30)',
        ],
    ],
], $results);
