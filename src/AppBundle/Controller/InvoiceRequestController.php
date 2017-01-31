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

    /**
     * @Rest\Get("api/invoice")
     */
    public function getAction() {
        $restresult = $this->getDoctrine()->getRepository('AppBundle:InvoiceRequest')->findAll();
        if ($restresult === null) {
            return new View("there are no users exist", Response::HTTP_NOT_FOUND);
        }
        return $restresult;
    }

    /**
    * @Rest\Post("/api/invoice")
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

    private function validateRequest(Request $request) {
        $missingFields = "";
        foreach (InvoiceRequestController::$mandatoryFields as $mandatoryField) {
            if(empty($request->get($mandatoryField))) {
                $missingFields = $missingFields . "· $mandatoryField\n";
            }
        }
        if (!empty($missingFields)) throw new NotAcceptableHttpException("Missing the following mandatory fields: \n" . $missingFields);
        if (!$this->validateCategory($request->get('category'))) throw new NotAcceptableHttpException("Invalid category. Must be on of: " . InvoiceCategory::getConstants());
    }

    private function validateCategory($category) {
        return (empty($category) || InvoiceCategory::isValidName($category));
    }

    private function retrieveUser($email, $phone, $address) {
        $user = $this->getDoctrine()->getRepository('AppBundle:User')->findOneByEmail($email);
        $logger = $this->get('logger');
        $logger->info('retrieveUser(): Found user with id: ' . $user->getId());
        if ($user === null) {
            /* create new user */
            $logger = $this->get('logger');
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
        }
        return $user;
    }
}