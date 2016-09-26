<?php

namespace App\Infrastructure\Provider;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Application;
use Silex\Provider\DoctrineServiceProvider as SilexDoctrineServiceProvider;

class DoctrineServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Container $app
     */
    public function register(Container $app)
    {
        $config = $app['configuration']['doctrine'];
        $doctrineConfiguration = $this->getDoctrineProfilesConfiguration($config);

        $app['orm.enabled'] = !empty($doctrineConfiguration);

        $app['orm.enabled.profile'] = function () use ($doctrineConfiguration) {
            return function ($profileName) use ($doctrineConfiguration) {
                return array_key_exists($profileName, $doctrineConfiguration);
            };
        };

        if ($app['orm.enabled']) {
            $app->register(new SilexDoctrineServiceProvider(), array(
                'dbs.options' => $this->getDbalConfiguration($doctrineConfiguration),
            ));

            $app['orm.ems'] = function (Application $app) use ($doctrineConfiguration) {
                $ems = array();

                foreach ($doctrineConfiguration as $profile => $config) {
                    $entityManager = EntityManager::create(
                        $app['dbs'][$profile],
                        Setup::createYAMLMetadataConfiguration(
                            array($config['orm']['config_dir']),
                            $app['debug'],
                            $config['orm']['proxy_dir'],
                            $this->getCacheImplementation($config['orm'])
                        ),
                        $app['dbs.event_manager'][$profile]
                    );

                    $ems[$profile] = $entityManager;
                }

                return $ems;
            };
        }
    }

    /**
     * @param Application $app
     * @param @codeCoverageIgnore
     */
    public function boot(Application $app)
    {
    }

    /**
     * @param array $config
     * @return CacheProvider
     */
    protected function getCacheImplementation(array $config)
    {
        if (array_key_exists('cache', $config)) {
            if ($config['cache'] == 'array') {
                return new ArrayCache();
            }

            // TODO: Add another caching options.
        } // @codeCoverageIgnore

        return new ArrayCache();  // @codeCoverageIgnore
    }

    /**
     * @param $configuration
     * @return array
     */
    protected function getDbalConfiguration($configuration)
    {
        $dbalConfiguration = array();

        foreach ($configuration as $profile => $config) {
            $dbalConfiguration[$profile] = $config['dbal'];
        }

        return $dbalConfiguration;
    }

    /**
     * @param $configuration
     * @return array
     */
    protected function getDoctrineProfilesConfiguration($configuration)
    {
        $doctrineProfiles = array();

        if ($this->isDoctrineEnabled($configuration)
            && array_key_exists('profiles', $configuration)
            && is_array($configuration['profiles'])
        ) {
            foreach ($configuration['profiles'] as $profileName => $config) {
                if (array_key_exists('enabled', $config)
                    && $config['enabled'] === TRUE
                    && is_array($config['dbal'])
                    && !empty($config['dbal'])
                    && is_array($config['orm'])
                    && !empty($config['orm'])
                ) {
                    $doctrineProfiles[$profileName] = $config;
                }
            }
        }

        return $doctrineProfiles;
    }

    /**
     * @param $configuration
     * @return bool
     */
    protected function isDoctrineEnabled($configuration)
    {
        return array_key_exists('enabled', $configuration) && (bool) $configuration['enabled'] === TRUE;
    }
}
