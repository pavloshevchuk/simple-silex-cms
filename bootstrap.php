<?php

date_default_timezone_set('Europe/London');

define('__ROOT_DIR__', __DIR__);
define('__CONFIGS_DIR__', __ROOT_DIR__ . DIRECTORY_SEPARATOR . 'configs');
define('__ROUTES_DIR__', __CONFIGS_DIR__ . DIRECTORY_SEPARATOR . 'routes');
define('__RUNTIME_DIR__', __ROOT_DIR__ . DIRECTORY_SEPARATOR . 'runtime');
define('__TEMPLATES_DIR__', __ROOT_DIR__ . DIRECTORY_SEPARATOR . 'templates');
define('__VENDOR_DIR__', __ROOT_DIR__ . DIRECTORY_SEPARATOR . 'vendor');

/**
 * @return \Silex\Application
 */
function application()
{
    $app = new \App\Infrastructure\Application();

    $app->register(new \App\Infrastructure\Provider\ConfigurationServiceProvider(),
                   array(
                       'configuration.dir' => __CONFIGS_DIR__,
                       'configuration.parameters' => array(
                           'root_dir' => __ROOT_DIR__,
                           'runtime_dir' => __RUNTIME_DIR__,
                           'config_dir' => __CONFIGS_DIR__,
                           'dir_separator' => DIRECTORY_SEPARATOR,
                       ),
                   )
    );

    $app['routes'] = $app->extend('routes', function (\Symfony\Component\Routing\RouteCollection $routes, \App\Infrastructure\Application $app) {
        $loader = new \Symfony\Component\Routing\Loader\YamlFileLoader(new \Symfony\Component\Config\FileLocator(__ROUTES_DIR__));
        $collection = $loader->load('routes.yml');
        $routes->addCollection($collection);

        return $routes;
    });

    $app->register(new \App\Infrastructure\Provider\DoctrineServiceProvider());
    $app->register(new \Silex\Provider\CsrfServiceProvider());
    $app->register(new \Silex\Provider\FormServiceProvider());
    $app->register(new \Silex\Provider\LocaleServiceProvider());
    $app->register(new \Silex\Provider\SessionServiceProvider());
    $app->register(new \Silex\Provider\ValidatorServiceProvider());

    $app->register(new \Silex\Provider\TranslationServiceProvider(), array(
        'translator.messages' => array(),
    ));

    $app->register(new \Silex\Provider\TwigServiceProvider(), array(
        'twig.options' => array(
            'cache' => FALSE,
            'strict_variables' => TRUE,
        ),
        'twig.path' => __TEMPLATES_DIR__,
        'twig.class_path' => array(
            __VENDOR_DIR__ . '/twig/lib',
            __VENDOR_DIR__ . '/twig-extentions/lib',
        ),
    ));

    $app->register(new \Silex\Provider\SecurityServiceProvider(), array(
        'security.firewalls' => array(
            'authorization' => array(
                'pattern' => '^/.*$',
                'anonymous' => TRUE,
                // Needed as the login path is under the secured area
                'form' => array(
                    'login_path' => '/login',
                    'check_path' => '/login/authorize',
                    'csrf_provider' => 'form.csrf_provider',
                ),
                'logout' => array(
                    'logout_path' => '/logout',
                ),
                'users' => function () use ($app) {
                    return new \App\Infrastructure\Provider\UserProvider($app['orm.ems']['mysql']);
                },
            ),
        ),
        'security.access_rules' => array(
            array('^/login$', 'IS_AUTHENTICATED_ANONYMOUSLY'),
            array('^/help$', 'IS_AUTHENTICATED_ANONYMOUSLY'),
            array('^/.*$', 'ROLE_USER'),
        ),
    ));

    $app->register(new \Knp\Provider\ConsoleServiceProvider(), array(
        'console.name' => 'MyApplication',
        'console.version' => '1.0.0',
        'console.project_directory' => __DIR__ . '/www',
    ));

    $app->error(function (\Exception $e, \Symfony\Component\HttpFoundation\Request $request, $code) use ($app) {
        $content = $app['twig']->render('error.html.twig', array('content' => $e->getMessage()));

        return new \Symfony\Component\HttpFoundation\Response($content, $code);
    });

    return $app;
}
