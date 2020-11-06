<?php

/**
 * Copyright (C) GrizzIT, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace Ulrack\AopExtension\Common;

use GrizzIt\Vfs\Common\FileSystemInterface;

interface GeneratorInterface
{
    /**
     * Retrieves the generated directory.
     *
     * @return FileSystemInterface
     */
    public function getGeneratedDirectory(): FileSystemInterface;

    /**
     * Generates the proxy class and returns the name.
     *
     * @param string $className
     *
     * @return string
     */
    public function generate(string $className): string;
}
