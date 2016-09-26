<?php

namespace App\Infrastructure\Controller;

use App\Infrastructure\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class BaseController
 *
 * @package App\Infrastructure\Controller\BaseController
 */
class IndexController
{
    /**
     * @param Request $request
     * @param Application $app
     * @return string
     */
    public function indexAction(Request $request, Application $app)
    {
        $token = $app['security.token_storage']->getToken();

        //$app['security.authorization_checker']->isGranted('IS_AUTHENTICATED_FULLY');
        return $app['twig']->render('index.twig', array(
            'username' => $token->getUser()->getUsername(),
        ));
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return string
     */
    public function loginAction(Request $request, Application $app)
    {
        return $app['twig']->render('login.twig', array(
            'error' => $app['security.last_error']($request),
            'last_username' => $app['session']->get('_security.last_username'),
        ));
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return string
     */
    public function helpAction(Request $request, Application $app)
    {
        return $app['twig']->render('help.twig');
    }
}
