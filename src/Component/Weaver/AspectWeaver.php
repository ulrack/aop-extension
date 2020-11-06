<?php

/**
 * Copyright (C) GrizzIT, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace Ulrack\AopExtension\Component\Weaver;

use Ulrack\AopExtension\Factory\ProxyFactory;
use Ulrack\AopExtension\Common\WeaverInterface;
use Ulrack\AopExtension\Common\CombinerInterface;
use Ulrack\AopExtension\Common\GeneratorInterface;
use Ulrack\Services\Common\ServiceFactoryInterface;

class AspectWeaver implements WeaverInterface
{
    /**
     * Contains the proxy generator.
     *
     * @var GeneratorInterface
     */
    private $proxyGenerator;

    /**
     * Contains the previously configured proxies.
     *
     * @var array
     */
    private $proxies = [];

    /**
     * Contains the service factory.
     *
     * @var ServiceFactoryInterface
     */
    private $serviceFactory;

    /**
     * Contains the compiler.
     *
     * @var CombinerInterface
     */
    private $combiner;

    /**
     * Constructor.
     *
     * @param ProxyFactory $proxyFactory
     * @param ServiceFactoryInterface $serviceFactory
     * @param CombinerInterface $combiner
     */
    public function __construct(
        GeneratorInterface $proxyGenerator,
        ServiceFactoryInterface $serviceFactory,
        CombinerInterface $combiner
    ) {
        $this->proxyGenerator = $proxyGenerator;
        $this->serviceFactory = $serviceFactory;
        $this->combiner = $combiner;
    }

    /**
     * Retrieves the combiner.
     *
     * @return CombinerInterface
     */
    public function getCombiner(): CombinerInterface
    {
        return $this->combiner;
    }

    /**
     * Weaves logic into an object.
     *
     * @param string $service
     * @param mixed $subject
     *
     * @return mixed
     */
    public function __invoke(string $service, $subject)
    {
        $objectHash = spl_object_hash($subject);
        if (isset($this->proxies[$objectHash])) {
            return $this->proxies[$objectHash];
        }

        $pluginConfiguration = $this->combiner->__invoke(
            $service,
            get_class($subject)
        );

        if (count($pluginConfiguration) > 0) {
            foreach ($pluginConfiguration as $method => $pointcut) {
                foreach ($pointcut['advices'] as $hook => $plugins) {
                    foreach ($plugins as $key => $plugin) {
                        $pluginConfiguration[$method]['advices'][$hook][$key] =
                            $this->serviceFactory->create(
                                $plugin
                            );
                    }
                }
            }

            $proxyName = $this->proxyGenerator->generate(get_class($subject));
            $this->proxies[$objectHash] = new $proxyName(
                $subject,
                $pluginConfiguration
            );

            return $this->proxies[$objectHash];
        }

        return $subject;
    }
}
