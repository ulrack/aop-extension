<?php

/**
 * Copyright (C) GrizzIT, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace Ulrack\AopExtension\Component\Proxy;

use GrizzIt\Storage\Component\ObjectStorage;

trait ProxyTrait
{
    /**
     * Contains the subject of the proxy.
     *
     * @var mixed
     */
    private $subject;

    /**
     * Contains the plugin configuration.
     *
     * @var array
     */
    private $pluginConfiguration;

    /**
     * Runs the plugins for the proxy.
     *
     * @param string $methodName
     * @param array $parameters
     *
     * @return mixed
     */
    public function runPlugins(string $methodName, array $parameters)
    {
        $parameters = new ObjectStorage($parameters);
        $subject = $this->subject;

        foreach (
            $this->pluginConfiguration[$methodName]['advices']['before'] ?? [] as $advice
        ) {
            $advice->before(
                $parameters,
                $methodName,
                $subject
            );
        }

        $proceed = (function () use ($parameters, $methodName, $subject) {
            return $subject->$methodName(
                ...array_values(iterator_to_array($parameters))
            );
        });

        foreach (
            array_reverse(
                $this->pluginConfiguration[$methodName]['advices']['around'] ?? []
            ) as $advice
        ) {
            $proceed = (function () use (
                $advice,
                $parameters,
                $methodName,
                $subject,
                $proceed
            ) {
                return $advice->around(
                    $parameters,
                    $methodName,
                    $subject,
                    $proceed
                );
            });
        }

        $return = $proceed();

        foreach (
            $this->pluginConfiguration[$methodName]['advices']['after'] ?? [] as $advice
        ) {
            $return = $advice->after(
                $parameters,
                $methodName,
                $this->subject,
                $return
            );
        }

        return $return;
    }
}
