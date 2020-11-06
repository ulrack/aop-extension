<?php

/**
 * Copyright (C) GrizzIT, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace Ulrack\AopExtension\Tests\Mock;

use Ulrack\AopExtension\Component\Proxy\ProxyTrait;

class TraitMock
{
    use ProxyTrait;

    /**
     * Constructor.
     *
     * @param mixed $subject
     * @param array $parameters
     */
    public function __construct($subject, array $parameters)
    {
        $this->subject = $subject;
        $this->pluginConfiguration = $parameters;
    }
}
