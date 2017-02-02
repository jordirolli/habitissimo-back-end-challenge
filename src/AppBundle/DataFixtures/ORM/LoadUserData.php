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

        /* Create another test user */
        $user2 = new User;
        $user2->setEmail('test2@test.com');
        $user2->setPhone('123456789');
        $user2->setAddress('Test user 2 address');
        $manager->persist($user2);
        $manager->flush();

        $this->addReference('test-user-1', $user);
        $this->addReference('test-user-2', $user2);
    }

    public function getOrder() {
        return 1;
    }
}