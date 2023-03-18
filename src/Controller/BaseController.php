<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * @method User getUser()
 */
abstract class BaseController extends AbstractController
{
    protected function redirectToReferer(): Response
    {
        $request = $this->container->get('request_stack')->getMainRequest();

        $referer = $request->headers->get('referer');

        return $this->redirect($referer);
    }
}
