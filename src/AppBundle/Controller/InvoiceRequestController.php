<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use FOS\RestBundle\View\View;
use AppBundle\Entity\InvoiceRequest;
use AppBundle\Entity\InvoiceCategory;
use AppBundle\Entity\InvoiceState;
use AppBundle\Entity\User;

class InvoiceRequestController extends FOSRestController {

    static $mandatoryFields = ['description','email','phone','address'];
    const PAGE_SIZE = 10;
    const PAGE_SIZE_MAX = 50;

    /**
     * @Rest\Get("api/invoices")
     */
    public function getAction(Request $request) {
        /* page size */
        $limit = (is_numeric($request->get('limit')) && $request->get('limit') <= self::PAGE_SIZE_MAX) ? $request->get('limit') : self::PAGE_SIZE;
        $start = is_numeric($request->get('start')) ? $request->get('start') : 0;
        if ($request->get('email')) {
            $restresult = $this->getDoctrine()->getRepository('AppBundle:InvoiceRequest')->findByEmail($request->get('email'), $start, $limit);
        } else {
            $restresult = $this->getDoctrine()->getRepository('AppBundle:InvoiceRequest')->findAll();
        }
        if (empty($restresult)) {
            return new View("No invoices found.", Response::HTTP_NOT_FOUND);
        }
        return $restresult;
    }

    /**
    * @Rest\Post("/api/invoices")
    */
    public function postAction(Request $request) {
        try {
            $this->validateRequest($request);
            $invoice = new InvoiceRequest;
            $user = $this->retrieveUser($request->get('email'), $request->get('phone'), $request->get('address'));
            $title = $request->get('title');
            $description = $request->get('description');
            $category = InvoiceCategory::fromName($request->get('category'));
            $invoice->setTitle($title);
            $invoice->setDescription($description);
            $invoice->setCategory($category);
            $invoice->setState(InvoiceState::Pending);
            $invoice->setUserId($user->getId());
            $em = $this->getDoctrine()->getManager();
            $em->persist($invoice);
            $em->flush();
            $logger = $this->get('logger');
            $logger->info('New invoice created with id: ' . $invoice->getId());
            return new View("Invoice request added successfully. New invoice id: " . $invoice->getId() , Response::HTTP_OK);
        } catch (NotAcceptableHttpException $nAHE) {
            $logger = $this->get('logger');
            $logger->error('InvoiceRequestController::postAction() NotAcceptableHttpException: ' + $nAHE->getMessage());
            return new View($nAHE->getMessage(), Response::HTTP_NOT_ACCEPTABLE);
        }
    }

    /**
    * @Rest\Put("/api/invoices/{id}")
    */
    public function putAction($id, Request $request) {
        $invoice = $this->getDoctrine()->getRepository('AppBundle:InvoiceRequest')->findOneById($id);
        if ($invoice != null) {
            if ($invoice->getState() != InvoiceState::Pending) {
                return new View("The given invoice can not be updated because it has already been published.", Response::HTTP_NOT_ACCEPTABLE);
            }
            if (!is_null($request->get('description')) && empty($request->get('description'))) {
                return new View("The description is a mandatory field.", Response::HTTP_NOT_ACCEPTABLE);
            }
            $title = ($request->get('title') != null)? $request->get('title') : $invoice->getTitle();
            $description = ($request->get('description') != null)? $request->get('description') : $invoice->getDescription();
            $category = ($request->get('category') != null)? InvoiceCategory::fromName($request->get('category')) : $invoice->getCategory();
            if ( $invoice->getTitle() != $title || $invoice->getDescription() != $description || $invoice->getCategory() != $category) {
                $invoice->setTitle($title);
                $invoice->setDescription($description);
                $invoice->setCategory($category);
                $em = $this->getDoctrine()->getManager();
                $em->persist($invoice);
                $em->flush();
                return new View("Invoice successfully updated.", Response::HTTP_OK);
            } else {
                return new View("Invoice does not require update.", Response::HTTP_NOT_MODIFIED);
            }
        } else {
            return new View("Invoice with id '$id' not found.", Response::HTTP_NOT_FOUND);
        }
    }

    /**
    * @Rest\Post("/api/invoices/{id}/action")
    */
    public function postActionAction($id, Request $request) {
         $invoice = $this->getDoctrine()->getRepository('AppBundle:InvoiceRequest')->findOneById($id);
         if ($invoice != null) {
            switch($request->get('type')) {
                case 'publish':
                    switch($invoice->getState()) {
                        case InvoiceState::Discarded:
                            return new View("Discarded invoices can not be re-published.", Response::HTTP_NOT_ACCEPTABLE);
                            break;
                        case InvoiceState::Published:
                            return new View("The invoice is already published.", Response::HTTP_NOT_MODIFIED);
                            break;
                        case InvoiceState::Pending:
                            if (empty($invoice->getTitle()) || empty($invoice->getCategory())) {
                                return new View("The invoice should have both title and category in order to be published.", Response::HTTP_NOT_ACCEPTABLE);
                            }
                            $invoice->setState(InvoiceState::Published);
                            $em = $this->getDoctrine()->getManager();
                            $em->persist($invoice);
                            $em->flush();
                            return new View("Invoice successfully published.", Response::HTTP_OK);
                            break;
                    }
                case 'discard':
                    switch($invoice->getState()) {
                        case InvoiceState::Pending:
                        case InvoiceState::Published:
                            $invoice->setState(InvoiceState::Discarded);
                            $em = $this->getDoctrine()->getManager();
                            $em->persist($invoice);
                            $em->flush();
                            return new View("The invoice has successfully been discarded.", Response::HTTP_OK);
                            break;
                        case InvoiceState::Discarded:
                            return new View("The invoice is already published.", Response::HTTP_NOT_MODIFIED);
                            break;
                    }
                    break;
            }
         } else {
            return new View("Invoice with id '$id' not found.", Response::HTTP_NOT_FOUND);
         }
    }

    private function validateRequest(Request $request) {
        $missingFields = "";
        foreach (InvoiceRequestController::$mandatoryFields as $mandatoryField) {
            if(empty($request->get($mandatoryField))) {
                $missingFields = $missingFields . "· $mandatoryField\n";
            }
        }
        if (!empty($missingFields)) throw new NotAcceptableHttpException("Missing the following mandatory fields: \n" . $missingFields);
        if (!$this->validateCategory($request->get('category'))) {
            $message = 'Invalid category. Must be on of: [';
            $validCategories = InvoiceCategory::getConstants();
            foreach ($validCategories as $validCategory) {
                $message = $message . "$validCategory";
                 if ($validCategory != end($validCategories)) {
                    $message = $message . ', ';
                 }
            }
            $message = $message . ']';
            throw new NotAcceptableHttpException($message);
        }
    }

    private function validateCategory($category) {
        return (empty($category) || InvoiceCategory::isValidName($category));
    }

    private function retrieveUser($email, $phone, $address) {
        $logger = $this->get('logger');
        $user = $this->getDoctrine()->getRepository('AppBundle:User')->findOneByEmail($email);
        if ($user === null) {
            /* create new user */
            $logger->info('User has not been found. Creating new user.');
            $user = new User;
            $user->setEmail($email);
            $user->setPhone($phone);
            $user->setAddress($address);
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();
            $logger->info('retrieveUser(): New user created with id: ' . $user->getId());
        } else if (($user->getPhone() != $phone) || ($user->getAddress() != $address)) {
            /* update existing user */
            $user->setPhone($phone);
            $user->setAddress($address);
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $logger->info('retrieveUser(): Existing user with id: ' . $user->getId() . ' has been updated.');
        } else {
            $logger->info('retrieveUser(): Found user with id: ' . $user->getId(). ', it does not require update.');

        }
        return $user;
    }
}