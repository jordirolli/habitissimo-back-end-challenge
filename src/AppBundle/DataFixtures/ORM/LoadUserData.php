<?php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Entity\User;

class LoadUserData extends AbstractFixture implements OrderedFixtureInterface {

    public function load(ObjectManager $manager) {

        /* Create a test user */
        $user = new User;
        $user->setEmail('test@test.com');
        $user->setPhone('123456789');
        $user->setAddress('Test user address');
        $manager->persist($user);
        $manager->flush();

        $this->addReference('test-user', $user);

    }

    public function getOrder() {
        return 1;
    }
}