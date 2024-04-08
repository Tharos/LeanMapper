<?php

declare(strict_types=1);


namespace {
    require_once __DIR__ . '/../bootstrap.php';

    class BaseEntity extends \LeanMapper\Entity
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
}

namespace MyNS {
    class WhenEnum {

        const NEVER = 'never';

        const ONCE = 'once';

        const EACH_TIME = 'eachTime';

    }
}

namespace MyNS2 {

    /**
     * @property int $id
     * @property string $state m:enum(self::STATE_*)
     * @property string $when m:enum(\MyNS\WhenEnum::*)
     */
    class Project extends \BaseEntity
    {

        const STATE_CREATED = 'created';

        const STATE_APPROVED = 'approved';

        const STATE_FINISHED = 'finished';

        const STATE_DELETED = 'deleted';

        const STATE_CANCELED = 'canceled';

    }
}



//////////


namespace {
    use Tester\Assert;

    //////////

    $project = new MyNS2\Project;

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

    $project->state = MyNS2\Project::STATE_CREATED;

    Assert::equal(MyNS2\Project::STATE_CREATED, $project->state);

    Assert::throws(
        function () use ($project) {
            $project->state = 'reopened';
        },
        LeanMapper\Exception\InvalidValueException::class,
        "Given value is not from possible values enumeration in property 'state' in entity MyNS2\\Project."
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

    $project->when = MyNS\WhenEnum::ONCE;

    Assert::equal(MyNS\WhenEnum::ONCE, $project->when);

    Assert::throws(
        function () use ($project) {
            $project->when = 'onceUponATime';
        },
        LeanMapper\Exception\InvalidValueException::class,
        "Given value is not from possible values enumeration in property 'when' in entity MyNS2\\Project."
    );
}
