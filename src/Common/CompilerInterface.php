<?php

/**
 * Copyright (C) GrizzIT, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace Ulrack\AopExtension\Common;

use GrizzIt\Storage\Common\StorageInterface;

interface CompilerInterface
{
    /**
     * Compiles the configuration and retrieves it.
     *
     * @return StorageInterface
     */
    public function __invoke(): StorageInterface;
}
