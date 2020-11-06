<?php

/**
 * Copyright (C) GrizzIT, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace Ulrack\AopExtension\Tests\Factory\Hook;

use stdClass;
use PHPUnit\Framework\TestCase;
use Ulrack\AopExtension\Common\WeaverInterface;
use Ulrack\AopExtension\Factory\Hook\ProxyHook;
use Ulrack\AopExtension\Common\CombinerInterface;
use Ulrack\Services\Common\ServiceFactoryInterface;

/**
 * @coversDefaultClass \Ulrack\AopExtension\Factory\Hook\ProxyHook
 */
class ProxyHookTest extends TestCase
{
    /**
     * @covers ::postCreate
     * @covers ::getAspectWeaver
     * @covers ::__construct
     *
     * @param string $serviceKey
     * @param mixed $return
     * @param mixed $expectedReturn
     *
     * @return void
     *
     * @dataProvider parameterProvider
     */
    public function testPostCreate(
        string $serviceKey,
        $return,
        $expectedReturn
    ): void {
        $serviceFactory = $this->createMock(ServiceFactoryInterface::class);
        $weaver = $this->createMock(WeaverInterface::class);
        $serviceFactory->method('create')
            ->with('services.aop.aspect.weaver')
            ->willReturn($weaver);

        $weaver->method('__invoke')
            ->with($serviceKey, $return)
            ->willReturn($expectedReturn);

        $weaver->method('getCombiner')
            ->willReturn($this->createMock(CombinerInterface::class));

        $key = 'global';
        $services = ['pointcuts' => []];
        $internalServices = ['service-factory' => $serviceFactory];
        $subject = new ProxyHook($key, [], $services, $internalServices);


        $this->assertEquals(
            [
                'serviceKey' => $serviceKey,
                'return' => $expectedReturn,
                'parameters' => []
            ],
            $subject->postCreate($serviceKey, $return, [])
        );
    }

    /**
     * @return array
     */
    public function parameterProvider(): array
    {
        $class = new stdClass();
        return [
            [
                'services.my',
                new stdClass(),
                new stdClass()
            ],
            [
                'services.aop.my',
                $class,
                $class
            ]
        ];
    }
}
