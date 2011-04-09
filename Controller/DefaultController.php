<?php

namespace Xnni\\MobileViewBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('XnniMobileViewBundle:Default:index.html.twig');
    }
}
