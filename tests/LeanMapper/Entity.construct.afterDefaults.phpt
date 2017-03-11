<?php

use LeanMapper\Entity;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

/**
 * @property null|string $firstname
 * @property string $surname
 * @property null|string $login
 */
class User extends LeanMapper\Entity
{
    protected function initAfterDefaults()
    {
        if (isset($this->surname)) {
            $this->login = strtolower($this->surname);

            if ($this->firstname !== null) {
                $this->login .= '.' . strtolower($this->firstname);
            }
        }
    }
}

//////////

$user = new User;
Assert::null($user->firstname);
Assert::null($user->login);

Assert::exception(
    function () use ($user) {
        $user->surname;
    },
    'LeanMapper\Exception\Exception',
    "Cannot get value of property 'surname' in entity User due to low-level failure: Missing 'surname' column in row with id -1."
);

//////////

$user = new User(
    [
        'surname' => 'Kohout',
    ]
);

Assert::null($user->firstname);
Assert::equal('Kohout', $user->surname);
Assert::equal('kohout', $user->login);

//////////

$user = new User(
    [
        'firstname' => 'Vojtech',
        'surname' => 'Kohout',
    ]
);

Assert::equal('Vojtech', $user->firstname);
Assert::equal('Kohout', $user->surname);
Assert::equal('kohout.vojtech', $user->login);
