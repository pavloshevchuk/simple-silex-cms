<?php

namespace App\Infrastructure\Provider;

use App\Infrastructure\Service\Configuration;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Application;

class ConfigurationServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Container $app
     */
    public function register(Container $app)
    {
        $app['configuration'] = function ($app) {

            $parameters = array();

            if (isset($app['configuration.parameters']) && is_array($app['configuration.parameters'])) {
                $parameters = $app['configuration.parameters'];
            }

            return new Configuration($app['configuration.dir'], $parameters);
        };
    }

    /**
     * @param Application $app
     */
    public function boot(Application $app)
    {
    }
}
