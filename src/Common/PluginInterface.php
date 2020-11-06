<?php

/**
 * Copyright (C) GrizzIT, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace Ulrack\AopExtension\Common;

use GrizzIt\Storage\Common\StorageInterface;

interface PluginInterface
{
    /**
     * Invoked before the method invocation.
     *
     * @param StorageInterface $parameters
     * @param string $methodName
     * @param mixed $subject
     *
     * @return void
     */
    public function before(
        StorageInterface $parameters,
        string $methodName,
        $subject
    ): void;

    /**
     * Invoked after the method invocation.
     *
     * @param StorageInterface $parameters
     * @param string $methodName
     * @param mixed $subject
     * @param mixed $return
     *
     * @return mixed
     */
    public function after(
        StorageInterface $parameters,
        string $methodName,
        $subject,
        $return
    );

    /**
     * Invoked around the method invocation.
     *
     * @param StorageInterface $parameters
     * @param string $methodName
     * @param mixed $subject
     * @param callable $proceed
     *
     * @return mixed
     */
    public function around(
        StorageInterface $parameters,
        string $methodName,
        $subject,
        callable $proceed
    );
}
