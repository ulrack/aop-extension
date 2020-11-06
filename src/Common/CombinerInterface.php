<?php

/**
 * Copyright (C) GrizzIT, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace Ulrack\AopExtension\Common;

interface CombinerInterface
{
    /**
     * Sets the configuration for the pointcuts.
     *
     * @param array $configuration
     *
     * @return void
     */
    public function setConfiguration(array $configuration): void;

    /**
     * Combines the configuration for a class and service.
     *
     * @param string $service
     * @param string $className
     *
     * @return array
     */
    public function __invoke(string $service, string $className): array;
}
