<?php

namespace ITDoors\SessionBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    /**
     * Extends user session
     *
     * @Route("/session/extend", name="session_extend")
     */
    public function indexExtendSession()
    {
        return new Response();
    }
}
