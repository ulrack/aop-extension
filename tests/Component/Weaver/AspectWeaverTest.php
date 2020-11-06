<?php

/**
 * Copyright (C) GrizzIT, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace Ulrack\AopExtension\Tests\Component\Weaver;

use stdClass;
use PHPUnit\Framework\TestCase;
use Ulrack\AopExtension\Common\CombinerInterface;
use Ulrack\AopExtension\Common\GeneratorInterface;
use Ulrack\Services\Common\ServiceFactoryInterface;
use Ulrack\AopExtension\Component\Weaver\AspectWeaver;

/**
 * @coversDefaultClass \Ulrack\AopExtension\Component\Weaver\AspectWeaver
 */
class AspectWeaverTest extends TestCase
{
    /**
     * @covers ::__invoke
     * @covers ::__construct
     * @covers ::getCombiner
     *
     * @return void
     */
    public function testInvoke(): void
    {
        $proxyGenerator = $this->createMock(GeneratorInterface::class);
        $serviceFactory = $this->createMock(ServiceFactoryInterface::class);
        $combiner = $this->createMock(CombinerInterface::class);
        $subject = new AspectWeaver($proxyGenerator, $serviceFactory, $combiner);

        $service = 'foo';
        $object = new stdClass();

        $this->assertEquals(
            $combiner,
            $subject->getCombiner()
        );

        $combiner->expects(static::once())
            ->method('__invoke')
            ->with('foo', stdClass::class)
            ->willReturn(
                [
                    '__invoke' => [
                        'advices' => [
                            'before' => [
                                'services.foo'
                            ]
                        ]
                    ]
                ]
            );

        $serviceFactory->expects(static::once())
            ->method('create')
            ->with('services.foo')
            ->willReturn(new stdClass());

        $proxyGenerator->expects(static::once())
            ->method('generate')
            ->with(stdClass::class)
            ->willReturn(stdClass::class);

        $return = spl_object_hash($subject->__invoke($service, $object));

        $this->assertNotEquals(spl_object_hash($object), $return);

        $this->assertEquals(
            $return,
            spl_object_hash($subject->__invoke($service, $object))
        );
    }

    /**
     * @covers ::__invoke
     * @covers ::__construct
     * @covers ::getCombiner
     *
     * @return void
     */
    public function testInvokeNoProxy(): void
    {
        $proxyGenerator = $this->createMock(GeneratorInterface::class);
        $serviceFactory = $this->createMock(ServiceFactoryInterface::class);
        $combiner = $this->createMock(CombinerInterface::class);
        $subject = new AspectWeaver($proxyGenerator, $serviceFactory, $combiner);

        $service = 'foo';
        $object = new stdClass();

        $this->assertEquals(
            $combiner,
            $subject->getCombiner()
        );

        $combiner->expects(static::once())
            ->method('__invoke')
            ->with('foo', stdClass::class)
            ->willReturn([]);

        $this->assertEquals($object, $subject->__invoke($service, $object));
    }
}
