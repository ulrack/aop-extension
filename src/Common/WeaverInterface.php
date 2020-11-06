<?php

/**
 * Copyright (C) GrizzIT, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace Ulrack\AopExtension\Common;

interface WeaverInterface
{
    /**
     * Retrieves the combiner.
     *
     * @return CombinerInterface
     */
    public function getCombiner(): CombinerInterface;

    /**
     * Weaves logic into an object.
     *
     * @param string $service
     * @param mixed $subject
     *
     * @return mixed
     */
    public function __invoke(string $service, $subject);
}
