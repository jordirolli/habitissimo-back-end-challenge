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

        /* Invoice 1: Partial 'Pending' invoice */
        $partialPendingInvoice = new InvoiceRequest();
        $partialPendingInvoice->setDescription('Partial pending invoice description');
        $partialPendingInvoice->setState(InvoiceState::Pending);
        $partialPendingInvoice->setUserId($this->getReference('test-user-1')->getId());
        $manager->persist($partialPendingInvoice);
        $manager->flush();

        /* Invoice 2: Complete 'Pending' invoice */
        $completePendingInvoice = new InvoiceRequest();
        $completePendingInvoice->setTitle('Complete pending invoice title');
        $completePendingInvoice->setDescription('Complete pending invoice description');
        $completePendingInvoice->setCategory(InvoiceCategory::Construction);
        $completePendingInvoice->setState(InvoiceState::Pending);
        $completePendingInvoice->setUserId($this->getReference('test-user-1')->getId());
        $manager->persist($completePendingInvoice);
        $manager->flush();

        /* Invoice 3: Complete 'Published' invoice */
        $completePublishedInvoice = new InvoiceRequest();
        $completePublishedInvoice->setTitle('Complete published invoice title');
        $completePublishedInvoice->setDescription('Complete published invoice description');
        $completePublishedInvoice->setCategory(InvoiceCategory::Construction);
        $completePublishedInvoice->setState(InvoiceState::Published);
        $completePublishedInvoice->setUserId($this->getReference('test-user-2')->getId());
        $manager->persist($completePublishedInvoice);
        $manager->flush();

        /* Invoice 4: Complete 'Discarded' invoice */
        $completeDiscardedInvoice = new InvoiceRequest();
        $completeDiscardedInvoice->setTitle('Complete published invoice title');
        $completeDiscardedInvoice->setDescription('Complete published invoice description');
        $completeDiscardedInvoice->setCategory(InvoiceCategory::Construction);
        $completeDiscardedInvoice->setState(InvoiceState::Discarded);
        $completeDiscardedInvoice->setUserId($this->getReference('test-user-2')->getId());
        $manager->persist($completeDiscardedInvoice);
        $manager->flush();
    }

    public function getOrder() {
        return 2;
    }
}