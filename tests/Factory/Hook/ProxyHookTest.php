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
use GrizzIt\Services\Common\Factory\ServiceFactoryInterface;
use GrizzIt\Services\Common\Compiler\ServiceCompilerInterface;

/**
 * @coversDefaultClass \Ulrack\AopExtension\Factory\Hook\ProxyHook
 */
class ProxyHookTest extends TestCase
{
    /**
     * @covers ::postCreate
     * @covers ::preCreate
     * @covers ::getAspectWeaver
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
        $serviceCompiler = $this->createMock(ServiceCompilerInterface::class);
        $serviceFactory->method('create')
            ->with('services.aop.aspect.weaver')
            ->willReturn($weaver);

        $weaver->method('__invoke')
            ->with($serviceKey, $return)
            ->willReturn($expectedReturn);

        $weaver->method('getCombiner')
            ->willReturn($this->createMock(CombinerInterface::class));

        $create = function (string $key) use ($weaver, $serviceCompiler) {
            if ($key === 'services.aop.aspect.weaver') {
                return $weaver;
            }

            if ($key === 'internal.core.service.compiler') {
                return $serviceCompiler;
            }
        };

        $subject = new ProxyHook();
        $subject->preCreate('key', 'definition', $create);
        $this->assertEquals(
            $expectedReturn,
            $subject->postCreate($serviceKey, [], $return, $create)
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
