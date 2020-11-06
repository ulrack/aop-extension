<?php

/**
 * Copyright (C) GrizzIT, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace Ulrack\AopExtension\Tests\Component\Combiner;

use PHPUnit\Framework\TestCase;
use GrizzIt\Cache\Common\CacheInterface;
use GrizzIt\Storage\Component\ObjectStorage;
use Ulrack\AopExtension\Command\AopClearCommand;
use Ulrack\Command\Common\Command\CommandInterface;
use Ulrack\AopExtension\Component\Combiner\ConfigurationCombiner;

/**
 * @coversDefaultClass \Ulrack\AopExtension\Component\Combiner\ConfigurationCombiner
 */
class ConfigurationCombinerTest extends TestCase
{
    /**
     * @covers ::__invoke
     * @covers ::__construct
     * @covers ::getCombinedConfiguration
     *
     * @return void
     */
    public function testInvokeKnown(): void
    {
        $cache = $this->createMock(CacheInterface::class);

        $cache->expects(static::once())
            ->method('exists')
            ->with('combined')
            ->willReturn(false);

        $cache->expects(static::once())
            ->method('store');

        $cacheContent = new ObjectStorage([
            'MyVendor\\MyPackage\\MyClass-services.foo' => ['config']
        ]);

        $cache->expects(static::once())
            ->method('fetch')
            ->with('combined')
            ->willReturn($cacheContent);


        $subject = new ConfigurationCombiner($cache);

        $service = 'services.foo';
        $className = '\\MyVendor\\MyPackage\\MyClass';

        $this->assertEquals(
            ['config'],
            $subject->__invoke($service, $className)
        );
    }

    /**
     * @covers ::__invoke
     * @covers ::__construct
     * @covers ::getCombinedConfiguration
     * @covers ::joinServiceConfiguration
     * @covers ::joinSimilarClassConfiguration
     * @covers ::setConfiguration
     *
     * @return void
     */
    public function testInvokeEmpty(): void
    {
        $cache = $this->createMock(CacheInterface::class);

        $cache->expects(static::once())
            ->method('exists')
            ->with('combined')
            ->willReturn(false);

        $cache->expects(static::exactly(2))
            ->method('store');

        $cache->expects(static::once())
            ->method('fetch')
            ->with('combined')
            ->willReturn(new ObjectStorage([]));

        $subject = new ConfigurationCombiner($cache);

        $service = 'services.foo';
        $className = '\\MyVendor\\MyPackage\\MyClass';

        $subject->setConfiguration([
            'classes' => [],
            'services' => []
        ]);

        $this->assertEquals([], $subject->__invoke($service, $className));
    }

    /**
     * @covers ::__invoke
     * @covers ::__construct
     * @covers ::getCombinedConfiguration
     * @covers ::mergeConfig
     * @covers ::joinServiceConfiguration
     * @covers ::joinSimilarClassConfiguration
     * @covers ::setConfiguration
     *
     * @return void
     */
    public function testInvoke(): void
    {
        $cache = $this->createMock(CacheInterface::class);

        $cache->expects(static::once())
            ->method('exists')
            ->with('combined')
            ->willReturn(false);

        $cache->expects(static::exactly(2))
            ->method('store');

        $cache->expects(static::once())
            ->method('fetch')
            ->with('combined')
            ->willReturn(new ObjectStorage([]));

        $subject = new ConfigurationCombiner($cache);

        $service = 'services.foo';
        $className = AopClearCommand::class;
        $subject->setConfiguration([
            'classes' => [
                AopClearCommand::class => [
                    '__invoke' => [
                        'explicit' => true,
                        'advices' => [
                            'before' => [
                                500 => [
                                    'foo'
                                ],
                                1000 => [
                                    'bar'
                                ]
                            ]
                        ]
                    ]
                ],
                CommandInterface::class => [
                    '__invoke' => [
                        'explicit' => false,
                        'advices' => [
                            'before' => [
                                1000 => [
                                    'baz'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'services' => [
                'services.foo' => [
                    '__invoke' => [
                        'advices' => [
                            'around' => [
                                1000 => [
                                    'qux'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $this->assertEquals([
            '__invoke' => [
                'advices' => [
                    'before' => [
                        'foo',
                        'bar',
                        'baz'
                    ],
                    'around' => [
                        'qux'
                    ]
                ]
            ]
        ], $subject->__invoke($service, $className));
    }
}
