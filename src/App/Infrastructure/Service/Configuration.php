<?php

namespace App\Infrastructure\Service;

use ArrayAccess;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Configuration
 *
 * @package PMP\Order\Infrastructure\Service
 */
class Configuration implements ArrayAccess
{
    /**
     * @var string $configDir
     */
    protected $configDir;

    /**
     * @var array $configuration
     */
    protected $configuration = array();

    /**
     * @var array $parameters
     */
    protected $parameters = array();

    /**
     * Configuration constructor.
     *
     * @param $configDir
     * @param array $parameters
     */
    public function __construct($configDir, $parameters = array())
    {
        $this->configDir = $configDir;
        $this->parameters = $parameters;

        $this->fulfillConfiguration(
            $this->getConfigurationFilePath()
        );

        $this->fulfillParameters();
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @codeCoverageIgnore
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->configuration[] = $value;
        }
        else {
            $this->configuration[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     * @return bool
     * @codeCoverageIgnore
     */
    public function offsetExists($offset)
    {
        return isset($this->configuration[$offset]);
    }

    /**
     * @param mixed $offset
     * @return null
     * @codeCoverageIgnore
     */
    public function offsetGet($offset)
    {
        return isset($this->configuration[$offset]) ? $this->configuration[$offset] : NULL;
    }

    /**
     * @param mixed $offset
     * @codeCoverageIgnore
     */
    public function offsetUnset($offset)
    {
        unset($this->configuration[$offset]);
    }

    /**
     * @return array
     * @codeCoverageIgnore
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @return string
     */
    protected function getConfigurationFilePath()
    {
        return $this->configDir . DIRECTORY_SEPARATOR . 'config.yml';
    }

    /**
     * @internal param string $configPath
     * @param $configFilePath
     */
    protected function fulfillConfiguration($configFilePath)
    {
        $configuration = $this->parseConfigurationFile($configFilePath);

        if (is_array($configuration)) {
            $this->mergeConfiguration(
                $this->handleImports($configuration)
            );
        }
    }

    /**
     * @param string $configPath
     * @return array
     */
    protected function parseConfigurationFile($configPath)
    {
        return Yaml::parse(file_get_contents($configPath));
    }

    /**
     * @param array $configuration
     * @return array
     */
    protected function handleImports(array $configuration)
    {
        foreach ($configuration as $key => $value) {
            if ($key == 'imports') {
                foreach ($value as $import) {
                    $this->fulfillConfiguration($this->configDir . DIRECTORY_SEPARATOR . $import['resource']);
                }
                unset($configuration['imports']);
            }
        }

        return $configuration;
    }

    /**
     * @param array $configuration
     */
    protected function mergeConfiguration(array $configuration)
    {
        $this->configuration = array_replace_recursive($this->configuration, $configuration);
    }

    /**
     * @return void
     */
    protected function fulfillParameters()
    {
        $parameters = $this->parameters;
        if (isset($this->configuration['parameters']) && is_array($this->configuration['parameters'])) {
            $updatedParameters = $this->replaceAllPlaceholdersWithParameters($this->configuration['parameters'], $this->parameters);
            $parameters = array_merge($parameters, $updatedParameters);
            unset($this->configuration['parameters']);
        }

        $this->configuration = $this->replaceAllPlaceholdersWithParameters($this->configuration, $parameters);
    }

    /**
     * @param array $data
     * @param array $parameters
     * @return array
     */
    protected function replaceAllPlaceholdersWithParameters(array $data, array $parameters)
    {
        $wrap = function (&$value) {
            $value = sprintf('/%s/', $value);
        };

        array_walk_recursive($data, function (&$param) use ($wrap, $parameters) {
            if (preg_match('/^%([^%]+)%$/', $param, $matches)) {
                $placeholder = $matches[1];
                if (isset($parameters[$placeholder])) {
                    $param = $parameters[$placeholder];
                }
            }
            elseif (preg_match_all('/%([^%]+)%/', $param, $matches)) {
                array_walk($matches[0], $wrap);
                $pattern = $matches[0];
                $replacement = array_intersect_key($parameters, array_flip($matches[1]));
                $param = preg_replace($pattern, $replacement, $param);
            }
        });

        return $data;
    }
}
