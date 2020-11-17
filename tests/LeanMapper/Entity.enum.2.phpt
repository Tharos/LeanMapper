<?php

declare(strict_types=1);

use LeanMapper\Entity;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

class BaseEntity extends Entity
{

    public function getEnumValues($propertyName)
    {
        $property = $this->getCurrentReflection()->getEntityProperty($propertyName);
        if (!$property->containsEnumeration()) {
            throw new InvalidArgumentException;
        }
        return $property->getEnumValues();
    }

}

class WhenEnum {

    const NEVER = 'never';

    const ONCE = 'once';

    const EACH_TIME = 'eachTime';

}

/**
 * @property int $id
 * @property string $state m:enum(self::STATE_*)
 * @property string $when m:enum(WhenEnum::*)
 */
class Project extends BaseEntity
{

    const STATE_CREATED = 'created';

    const STATE_APPROVED = 'approved';

    const STATE_FINISHED = 'finished';

    const STATE_DELETED = 'deleted';

    const STATE_CANCELED = 'canceled';

}

//////////

$project = new Project;

Assert::equal(
    [
        'STATE_CREATED' => 'created',
        'STATE_APPROVED' => 'approved',
        'STATE_FINISHED' => 'finished',
        'STATE_DELETED' => 'deleted',
        'STATE_CANCELED' => 'canceled',
    ],
    $project->getEnumValues('state')
);

$project->state = Project::STATE_CREATED;

Assert::equal(Project::STATE_CREATED, $project->state);

Assert::throws(
    function () use ($project) {
        $project->state = 'reopened';
    },
    LeanMapper\Exception\InvalidValueException::class,
    "Given value is not from possible values enumeration in property 'state' in entity Project."
);

//////////

Assert::equal(
    [
        'NEVER' => 'never',
        'ONCE' => 'once',
        'EACH_TIME' => 'eachTime',
    ],
    $project->getEnumValues('when')
);

$project->when = WhenEnum::ONCE;

Assert::equal(WhenEnum::ONCE, $project->when);

Assert::throws(
    function () use ($project) {
        $project->when = 'onceUponATime';
    },
    LeanMapper\Exception\InvalidValueException::class,
    "Given value is not from possible values enumeration in property 'when' in entity Project."
);
