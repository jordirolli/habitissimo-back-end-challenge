<?php

namespace Tests\AppBundle\Controller;

use AppBundle\DataFixtures\ORM\LoadUserData;
use AppBundle\DataFixtures\ORM\LoadInvoiceRequestData;
use AppBundle\Entity\InvoiceState;

use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


class InvoiceRequestControllerTest extends WebTestCase {

    protected function setUp() {
        $loader = new Loader();
        $loader->addFixture(new LoadUserData());
        $loader->addFixture(new LoadInvoiceRequestData());
        $purger = new ORMPurger();
        $executor = new ORMExecutor(static::createClient()->getContainer()->get('doctrine.orm.entity_manager'), $purger);
        $executor->execute($loader->getFixtures());
    }

    public function testOKGetInvoices() {
        $client = static::createClient();

        $client->request('GET', '/api/invoices');

        // Assert that the "Content-Type" header is "application/json"
        $this->assertTrue(
            $client->getResponse()->headers->contains(
                'Content-Type',
                'application/json'
            ),
            'the "Content-Type" header is "application/json"' // optional message shown on failure
        );
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $result = json_decode($client->getResponse()->getContent());
        $this->assertEquals(4,sizeof($result));
    }

    public function testKOCreateInvoiceWithoutMandatoryFields() {
        $client = static::createClient();

        $client->request('POST', '/api/invoices');

        // Assert that the "Content-Type" header is "application/json"
        $this->assertTrue(
            $client->getResponse()->headers->contains(
                'Content-Type',
                'application/json'
            ),
            'the "Content-Type" header is "application/json"' // optional message shown on failure
        );
        /* Not acceptable */
        $this->assertEquals(406, $client->getResponse()->getStatusCode());
        /* The error message should detail all the required fields being missed */
        $this->assertContains('description', $client->getResponse()->getContent());
        $this->assertContains('email', $client->getResponse()->getContent());
        $this->assertContains('phone', $client->getResponse()->getContent());
        $this->assertContains('address', $client->getResponse()->getContent());
    }

    public function testKOCreateInvoiceWithInvalidCategory() {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/invoices',
            array(
                'description'   => 'functional test description',
                'category'      => 'non existing',
                'email'         => 'does@not.matter',
                'phone'         => 666666666,
                'address'       => 'new address'
            )
        );

        // Assert that the "Content-Type" header is "application/json"
        $this->assertTrue(
            $client->getResponse()->headers->contains(
                'Content-Type',
                'application/json'
            ),
            'the "Content-Type" header is "application/json"' // optional message shown on failure
        );
        /* Not acceptable response */
        $this->assertEquals(406, $client->getResponse()->getStatusCode());
        $this->assertContains('Invalid category', $client->getResponse()->getContent());
    }

    public function testOKCreateInvoiceWithExistingUser() {
        /* TODO use fixture reference */
        // new LoadUserData()->getReference('test-user');
        $client = static::createClient();

        $manager = $client->getContainer()->get('doctrine.orm.entity_manager');
        $existingUser = $manager->getRepository('AppBundle:User')->findOneByEmail('test@test.com');

        $client->request(
            'POST',
            '/api/invoices',
            array(
                'description'   => 'functional test description',
                'category'      => 'construction',
                'email'         => $existingUser->getEmail(),
                'phone'         => 666666666,
                'address'       => 'new address'
            )
        );

        // Assert that the "Content-Type" header is "application/json"
        $this->assertTrue(
            $client->getResponse()->headers->contains(
                'Content-Type',
                'application/json'
            ),
            'the "Content-Type" header is "application/json"' // optional message shown on failure
        );
        /* Not acceptable response */
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        /* There should only be one user in the DB */
        $users = $manager->getRepository('AppBundle:User')->findAll();
        $this->assertEquals(1, sizeof($users));
        /* The user email and address should be updated */
        $this->assertEquals(666666666, $users[0]->getPhone());
        $this->assertEquals('new address', $users[0]->getAddress());
    }

    public function testOKCreateInvoiceWithNewUser() {

        $client = static::createClient();
        $manager = $client->getContainer()->get('doctrine.orm.entity_manager');

        $client->request(
            'POST',
            '/api/invoices',
            array(
                'description'   => 'functional test description',
                'category'      => 'construction',
                'email'         => 'new@user.com',
                'phone'         => 666666666,
                'address'       => 'new address'
            )
        );

        // Assert that the "Content-Type" header is "application/json"
        $this->assertTrue(
            $client->getResponse()->headers->contains(
                'Content-Type',
                'application/json'
            ),
            'the "Content-Type" header is "application/json"' // optional message shown on failure
        );
        /* Not acceptable response */
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        /* There should only be one user in the DB */
        $users = $manager->getRepository('AppBundle:User')->findAll();
        $this->assertEquals(2, sizeof($users));
        /* The new user has the data posted to the service  */
        $this->assertEquals('new@user.com', $users[1]->getEmail());
        $this->assertEquals(666666666, $users[1]->getPhone());
        $this->assertEquals('new address', $users[1]->getAddress());
    }

    public function testKOUpdateNonExistingInvoice() {
        $client = static::createClient();

        $client->request(
            'PUT',
            '/api/invoices/not_valid'
        );

        // Assert that the "Content-Type" header is "application/json"
        $this->assertTrue(
            $client->getResponse()->headers->contains(
                'Content-Type',
                'application/json'
            ),
            'the "Content-Type" header is "application/json"' // optional message shown on failure
        );
        /* Not found response */
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertEquals('"Invoice with id \'not_valid\' not found."', $client->getResponse()->getContent());
    }

    public function testKOUpdatePublishedInvoice() {
        $client = static::createClient();
        $manager = $client->getContainer()->get('doctrine.orm.entity_manager');
        $invalidInvoice = $manager->getRepository('AppBundle:InvoiceRequest')->findOneByState(InvoiceState::Published);

        $client->request(
            'PUT',
            '/api/invoices/' . $invalidInvoice->getId()
        );

        // Assert that the "Content-Type" header is "application/json"
        $this->assertTrue(
            $client->getResponse()->headers->contains(
                'Content-Type',
                'application/json'
            ),
            'the "Content-Type" header is "application/json"' // optional message shown on failure
        );
        /* Not acceptable response */
        $this->assertEquals(406, $client->getResponse()->getStatusCode());
        $this->assertEquals('"The given invoice can not be updated because it has already been published."', $client->getResponse()->getContent());
    }

    public function testKOSetEmptyDescriptionToInvoice() {
        $client = static::createClient();
        $manager = $client->getContainer()->get('doctrine.orm.entity_manager');
        $validInvoice = $manager->getRepository('AppBundle:InvoiceRequest')->findOneByState(InvoiceState::Pending);

        $client->request(
            'PUT',
            '/api/invoices/' . $validInvoice->getId(),
            array(
                'description'   => ''
            )
        );

        // Assert that the "Content-Type" header is "application/json"
        $this->assertTrue(
            $client->getResponse()->headers->contains(
                'Content-Type',
                'application/json'
            ),
            'the "Content-Type" header is "application/json"' // optional message shown on failure
        );
        /* Not acceptable response */
        $this->assertEquals(406, $client->getResponse()->getStatusCode());
        $this->assertEquals('"The description is a mandatory field."', $client->getResponse()->getContent());
    }

    public function testOKUpdateValidInvoice() {
        $client = static::createClient();
        $manager = $client->getContainer()->get('doctrine.orm.entity_manager');
        $validInvoice = $manager->getRepository('AppBundle:InvoiceRequest')->findOneByState(InvoiceState::Pending);

        $client->request(
            'PUT',
            '/api/invoices/' . $validInvoice->getId(),
            array(
                'title'         => 'new title',
                'description'   => 'new description',
                'category'      => 'reform'
            )
        );

        // Assert that the "Content-Type" header is "application/json"
        $this->assertTrue(
            $client->getResponse()->headers->contains(
                'Content-Type',
                'application/json'
            ),
            'the "Content-Type" header is "application/json"' // optional message shown on failure
        );
        /* Valid response */
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('"Invoice successfully updated."', $client->getResponse()->getContent());
        /* The invoice has been properly updated */
        $updatedInvoice = $manager->getRepository('AppBundle:InvoiceRequest')->findOneById($validInvoice->getId());
        $this->assertEquals('new title',$updatedInvoice->getTitle());
        $this->assertEquals('new description',$updatedInvoice->getDescription());
        $this->assertEquals('Reform',$updatedInvoice->getCategory());
    }

    public function testKOPublishPublishedInvoice() {
        $client = static::createClient();
        $manager = $client->getContainer()->get('doctrine.orm.entity_manager');
        $publishedInvoice = $manager->getRepository('AppBundle:InvoiceRequest')->findOneByState(InvoiceState::Published);

        $client->request(
            'POST',
            '/api/invoices/' . $publishedInvoice->getId() . '/action?type=publish'
        );

        /* Not modified */
        $this->assertEquals(304, $client->getResponse()->getStatusCode());
    }

    public function testKOPublishDiscardedInvoice() {
        $client = static::createClient();
        $manager = $client->getContainer()->get('doctrine.orm.entity_manager');
        $publishedInvoice = $manager->getRepository('AppBundle:InvoiceRequest')->findOneByState(InvoiceState::Discarded);

        $client->request(
            'POST',
            '/api/invoices/' . $publishedInvoice->getId() . '/action?type=publish'
        );

        // Assert that the "Content-Type" header is "application/json"
        $this->assertTrue(
            $client->getResponse()->headers->contains(
                'Content-Type',
                'application/json'
            ),
            'the "Content-Type" header is "application/json"' // optional message shown on failure
        );
        /* Not acceptable response */
        $this->assertEquals(406, $client->getResponse()->getStatusCode());
        $this->assertEquals('"Discarded invoices can not be re-published."', $client->getResponse()->getContent());
    }

    public function testKOPendingInvoiceWithoutTitle() {
        $client = static::createClient();
        $manager = $client->getContainer()->get('doctrine.orm.entity_manager');
        $publishedInvoice = $manager->getRepository('AppBundle:InvoiceRequest')->findOneByTitle(null);

        $client->request(
            'POST',
            '/api/invoices/' . $publishedInvoice->getId() . '/action?type=publish'
        );

        // Assert that the "Content-Type" header is "application/json"
        $this->assertTrue(
            $client->getResponse()->headers->contains(
                'Content-Type',
                'application/json'
            ),
            'the "Content-Type" header is "application/json"' // optional message shown on failure
        );
        /* Not acceptable response */
        $this->assertEquals(406, $client->getResponse()->getStatusCode());
        $this->assertEquals('"The invoice should have both title and category in order to be published."', $client->getResponse()->getContent());
    }

    public function testOKPublishInvoice() {
        $client = static::createClient();
        $manager = $client->getContainer()->get('doctrine.orm.entity_manager');
        $pendingInvoices = $manager->getRepository('AppBundle:InvoiceRequest')->findByState(InvoiceState::Pending);
        foreach($pendingInvoices as $pendingInvoice) {
            if (!empty($pendingInvoice->getTitle()) && !empty($pendingInvoice->getCategory())) break;
        }
        $client->request(
            'POST',
            '/api/invoices/' . $pendingInvoice->getId() . '/action?type=publish'
        );

        // Assert that the "Content-Type" header is "application/json"
        $this->assertTrue(
            $client->getResponse()->headers->contains(
                'Content-Type',
                'application/json'
            ),
            'the "Content-Type" header is "application/json"' // optional message shown on failure
        );
        /* Valid response */
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('"Invoice successfully published."', $client->getResponse()->getContent());
        /* The invoice has been properly updated */
        $updatedInvoice = $manager->getRepository('AppBundle:InvoiceRequest')->findOneById($pendingInvoice->getId());
        $this->assertEquals(InvoiceState::Published,$pendingInvoice->getState());
    }
}
