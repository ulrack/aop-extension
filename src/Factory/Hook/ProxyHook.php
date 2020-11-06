<?php

/**
 * Copyright (C) GrizzIT, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace Ulrack\AopExtension\Factory\Hook;

use Ulrack\AopExtension\Common\WeaverInterface;
use Ulrack\Services\Common\ServiceFactoryInterface;
use Ulrack\Services\Common\Hook\AbstractServiceFactoryHook;

class ProxyHook extends AbstractServiceFactoryHook
{
    /**
     * Contains the aspect weaver.
     *
     * @var WeaverInterface
     */
    private $aspectWeaver;

    /**
     * Retrieves the aspect weaver.
     *
     * @return WeaverInterface
     */
    private function getAspectWeaver(): WeaverInterface
    {
        if (is_null($this->aspectWeaver)) {
            /** @var ServiceFactoryInterface $serviceFactory */
            $serviceFactory = $this->getInternalService('service-factory');
            $this->aspectWeaver = $serviceFactory->create(
                'services.aop.aspect.weaver'
            );

            $this->aspectWeaver->getCombiner()->setConfiguration(
                $this->getServices()['pointcuts']
            );
        }

        return $this->aspectWeaver;
    }

    /**
     * Hooks in after the creation of a service.
     *
     * @param string $serviceKey
     * @param mixed $return
     * @param array $parameters
     *
     * @return array
     */
    public function postCreate(
        string $serviceKey,
        $return,
        array $parameters = []
    ): array {
        if (
            is_object($return) &&
            strpos($serviceKey, '.aop.') === false &&
            strpos($serviceKey, '.core.') === false
        ) {
            $return = $this->getAspectWeaver()->__invoke(
                $serviceKey,
                $return
            );
        }

        return [
            'serviceKey' => $serviceKey,
            'return' => $return,
            'parameters' => $parameters
        ];
    }
}
