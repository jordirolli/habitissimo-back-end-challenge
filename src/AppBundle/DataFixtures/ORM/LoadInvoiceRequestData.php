<?php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Entity\InvoiceCategory;
use AppBundle\Entity\InvoiceRequest;
use AppBundle\Entity\InvoiceState;

class LoadInvoiceRequestData extends AbstractFixture implements OrderedFixtureInterface {

    public function load(ObjectManager $manager) {

        /* Invoice 1: It does have all the fields. State: 'Pending'*/
        $invoice1 = new InvoiceRequest();
        $invoice1->setTitle('Fixture 1 title');
        $invoice1->setDescription('Fixture 1 description');
        $invoice1->setCategory(InvoiceCategory::Construction);
        $invoice1->setState(InvoiceState::Pending);
        $invoice1->setUserId($this->getReference('test-user')->getId());
        $manager->persist($invoice1);
        $manager->flush();

        /* Invoice 2: It does have mandatory fields. State: 'Published'*/
        $invoice2 = new InvoiceRequest();
        $invoice2->setDescription('Fixture 2 description');
        $invoice2->setState(InvoiceState::Published);
        $invoice2->setUserId($this->getReference('test-user')->getId());
        $manager->persist($invoice2);
        $manager->flush();
    }

    public function getOrder() {
        return 2;
    }
}