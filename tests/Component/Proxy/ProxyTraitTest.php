<?php

/**
 * Copyright (C) GrizzIT, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace Ulrack\AopExtension\Tests\Component\Proxy;

use PHPUnit\Framework\TestCase;
use GrizzIt\Storage\Common\StorageInterface;
use Ulrack\AopExtension\Tests\Mock\TraitMock;
use Ulrack\AopExtension\Common\PluginInterface;
use Ulrack\AopExtension\Component\Proxy\ProxyTrait;

/**
 * @coversDefaultClass \Ulrack\AopExtension\Component\Proxy\ProxyTrait
 */
class ProxyTraitTest extends TestCase
{
    /**
     * @covers ::runPlugins
     *
     * @return void
     */
    public function testRunPlugins(): void
    {
        $object = (new class {
            public function test(string $parameter)
            {
                return $parameter;
            }
        });

        $beforePlugin = $this->createMock(PluginInterface::class);
        $beforePlugin->expects(static::once())
            ->method('before')
            ->with(
                $this->isInstanceOf(StorageInterface::class),
                'test',
                $object
            );

        $aroundPlugin = $this->createMock(PluginInterface::class);
        $aroundPlugin->expects(static::once())
            ->method('around')
            ->will(
                $this->returnCallback(function (
                    StorageInterface $parameters,
                    string $methodName,
                    $subject,
                    callable $proceed
                ) {
                    return $proceed();
                })
            );

        $afterPlugin = $this->createMock(PluginInterface::class);
        $afterPlugin->expects(static::once())
            ->method('after')
            ->with(
                $this->isInstanceOf(StorageInterface::class),
                'test',
                $object,
                'foo'
            )->willReturn('foo');

        $subject = new TraitMock(
            $object,
            [
                'test' => [
                    'advices' => [
                        'before' => [
                            $beforePlugin
                        ],
                        'around' => [
                            $aroundPlugin
                        ],
                        'after' => [
                            $afterPlugin
                        ]
                    ]
                ]
            ]
        );

        $methodName = 'test';
        $parameters = [
            'parameter' => 'foo'
        ];

        $subject->runPlugins($methodName, $parameters);
    }
}
