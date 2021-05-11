<?php

/**
 * Copyright (C) GrizzIT, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace Ulrack\AopExtension\Component\Combiner;

use GrizzIt\Cache\Common\CacheInterface;
use GrizzIt\Storage\Common\StorageInterface;
use GrizzIt\Storage\Component\ObjectStorage;
use Ulrack\AopExtension\Common\CombinerInterface;

class ConfigurationCombiner implements CombinerInterface
{
    /**
     * Contains the AOP cache.
     *
     * @var CacheInterface
     */
    private $cache;

    /**
     * Contains the combined configuration.
     *
     * @var StorageInterface
     */
    private $combinedConfiguration;

    /**
     * Contains the compiled configuration.
     *
     * @var array
     */
    private $configuration = [];

    /**
     * Constructor.
     *
     * @param CacheInterface $cache
     */
    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Sets the configuration for the pointcuts.
     *
     * @param array $configuration
     *
     * @return void
     */
    public function setConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
    }

    /**
     * Retrieves the combined configuration storage.
     *
     * @return StorageInterface
     */
    private function getCombinedConfiguration(): StorageInterface
    {
        if (is_null($this->combinedConfiguration)) {
            if (!$this->cache->exists('combined')) {
                $this->cache->store('combined', new ObjectStorage());
            }

            $this->combinedConfiguration = $this->cache->fetch('combined');
        }

        return $this->combinedConfiguration;
    }

    /**
     * Merges a configuration entry into the main configuration.
     *
     * @param array $adviceConfig
     * @param string $method
     * @param array $config
     *
     * @return array
     */
    private function mergeConfig(
        array $adviceConfig,
        string $method,
        array $config
    ): array {
        $newConfig = $config;
        if (isset($adviceConfig[$method])) {
            $newConfig = $adviceConfig[$method];
            foreach ($config['advices'] ?? [] as $hook => $advices) {
                foreach ($advices as $sortOrder => $advice) {
                    $newConfig['advices'][$hook][$sortOrder] = array_merge(
                        $newConfig['advices'][$hook][$sortOrder] ?? [],
                        $advice
                    );
                }
            }
        }

        $adviceConfig[$method] = $newConfig;

        return $adviceConfig;
    }

    /**
     * Joins in the service based configuration.
     *
     * @param array $adviceConfig
     * @param array $configuration
     * @param string $service
     *
     * @return array
     */
    private function joinServiceConfiguration(
        array $adviceConfig,
        array $configuration,
        string $service
    ): array {
        $serviceConfig = $configuration['services'] ?? [];

        if (isset($serviceConfig[$service])) {
            foreach ($serviceConfig[$service] as $method => $config) {
                $adviceConfig = $this->mergeConfig(
                    $adviceConfig,
                    $method,
                    $config
                );
            }
        }

        return $adviceConfig;
    }

    /**
     * Joins in similar class configurations.
     *
     * @param array $adviceConfig
     * @param array $configuration
     * @param string $className
     *
     * @return array
     */
    private function joinSimilarClassConfiguration(
        array $adviceConfig,
        array $configuration,
        string $className
    ): array {
        foreach ($configuration['classes'] ?? [] as $class => $classConfig) {
            if ($class !== $className && is_a($className, $class, true)) {
                foreach ($classConfig as $method => $config) {
                    if (($config['explicit'] ?? false) === false) {
                        $adviceConfig = $this->mergeConfig(
                            $adviceConfig,
                            $method,
                            $config
                        );
                    }
                }
            }
        }

        return $adviceConfig;
    }

    /**
     * Combines the configuration for a class and service.
     *
     * @param string $service
     * @param string $className
     *
     * @return array
     */
    public function __invoke(string $service, string $className): array
    {
        $className = ltrim($className, '\\');
        $combinedConfig = $this->getCombinedConfiguration();
        $checkHash = $className . '-' . $service;
        if ($combinedConfig->has($checkHash)) {
            return $combinedConfig->get($checkHash);
        }

        $adviceConfig = $this->joinSimilarClassConfiguration(
            $this->joinServiceConfiguration(
                $this->configuration['classes'][$className] ?? [],
                $this->configuration,
                $service
            ),
            $this->configuration,
            $className
        );

        $storeConfig = [];
        foreach ($adviceConfig as $method => $advices) {
            ksort($advices['advices']);
            $storeConfig[$method]['advices'] = [];
            foreach ($adviceConfig[$method]['advices'] as $hook => $advice) {
                $adviceConfig[$method]['advices'][$hook] = array_merge(...$advice);

                foreach ($adviceConfig[$method]['advices'][$hook] as $advice) {
                    $storeConfig[$method]['advices'][$hook][] = $advice;
                }
            }
        }

        $combinedConfig->set($checkHash, $storeConfig);
        $this->cache->store('combined', $this->combinedConfiguration);

        return $storeConfig;
    }
}
