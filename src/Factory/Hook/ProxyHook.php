<?php

/**
 * Copyright (C) GrizzIT, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace Ulrack\AopExtension\Factory\Hook;

use Ulrack\AopExtension\Common\WeaverInterface;
use GrizzIt\Services\Common\Factory\ServiceFactoryHookInterface;

class ProxyHook implements ServiceFactoryHookInterface
{
    /**
     * Contains the aspect weaver.
     *
     * @var WeaverInterface
     */
    private ?WeaverInterface $aspectWeaver = null;

    /**
     * Retrieves the aspect weaver.
     *
     * @param callable $create
     *
     * @return WeaverInterface
     */
    private function getAspectWeaver(callable $create): WeaverInterface
    {
        if (is_null($this->aspectWeaver)) {
            $this->aspectWeaver = $create('services.aop.aspect.weaver');
            $registry = $create('internal.core.service.compiler')->compile();
            $this->aspectWeaver->getCombiner()->setConfiguration(
                [
                    'services' => $registry->getDefinitionByKey(
                        'pointcuts.services'
                    ),
                    'classes' => $registry->getDefinitionByKey(
                        'pointcuts.classes'
                    )
                ]
            );
        }

        return $this->aspectWeaver;
    }

    /**
     * Hooks in before the creation of a service.
     *
     * @param string $key
     * @param mixed $definition
     * @param callable $create
     *
     * @return array
     */
    public function preCreate(
        string $key,
        mixed $definition,
        callable $create
    ): array {
        return [$key, $definition];
    }

    /**
     * Hooks in after the creation of a service.
     *
     * @param string $key
     * @param mixed $definition
     * @param mixed $return
     * @param callable $create
     *
     * @return mixed
     */
    public function postCreate(
        string $key,
        mixed $definition,
        mixed $return,
        callable $create
    ): mixed {
        if (
            is_object($return) &&
            strpos($key, '.aop.') === false &&
            strpos($key, '.core.') === false
        ) {
            $return = $this->getAspectWeaver($create)->__invoke(
                $key,
                $return
            );
        }

        return $return;
    }
}
