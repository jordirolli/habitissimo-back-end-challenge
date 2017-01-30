<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\View\View;
use AppBundle\Entity\InvoiceRequest;
use AppBundle\Entity\User;

class InvoiceRequestController extends FOSRestController
{

    /**
     * @Rest\Get("api/invoice")
     */
    public function getAction()
    {
      $restresult = $this->getDoctrine()->getRepository('AppBundle:InvoiceRequest')->findAll();
        if ($restresult === null) {
          return new View("there are no users exist", Response::HTTP_NOT_FOUND);
     }
        return $restresult;
    }
}