<?php

/**
 * Copyright (C) GrizzIT, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace Ulrack\AopExtension\Tests\Component\Compiler\Extension;

use PHPUnit\Framework\TestCase;
use Ulrack\Services\Common\ServiceRegistryInterface;
use GrizzIt\Validator\Component\Logical\AlwaysValidator;
use Ulrack\AopExtension\Component\Compiler\Extension\AopCompiler;

/**
 * @coversDefaultClass \Ulrack\AopExtension\Component\Compiler\Extension\AopCompiler
 */
class AopCompilerTest extends TestCase
{
    /**
     * @covers ::compile
     * @covers ::sortAdvices
     * @covers ::compileConfiguration
     * @covers ::__construct
     *
     * @return void
     */
    public function testCompileEmpty(): void
    {
        $registry = $this->createMock(ServiceRegistryInterface::class);
        $validator = new AlwaysValidator(true);
        $getHooks = (function () {
            return [];
        });

        $subject = new AopCompiler(
            $registry,
            'pointcuts',
            $validator,
            [],
            $getHooks
        );

        $services = ['pointcuts' => []];
        $this->assertEquals(['pointcuts' => [
            'services' => [],
            'classes' => []
        ]], $subject->compile($services));
    }

    /**
     * @covers ::compile
     * @covers ::sortAdvices
     * @covers ::compileConfiguration
     * @covers ::__construct
     *
     * @return void
     */
    public function testCompile(): void
    {
        $registry = $this->createMock(ServiceRegistryInterface::class);
        $validator = new AlwaysValidator(true);
        $getHooks = (function () {
            return [];
        });

        $subject = new AopCompiler(
            $registry,
            'pointcuts',
            $validator,
            [],
            $getHooks
        );

        $services = [
            'pointcuts' => [
                [
                    'join-point' => 'foo',
                    'advice' => 'bar',
                    'sortOrder' => 100
                ],
                [
                    'join-point' => 'baz',
                    'advice' => 'bar',
                    'sortOrder' => 200
                ]
            ],
            'advices' => [
                'bar' => [
                    'service' => 'baz',
                    'hook' => 'after'
                ]
            ],
            'join-points' => [
                'foo' => [
                    'class' => 'qux',
                    'method' => '__invoke',
                    'explicit' => true
                ],
                'baz' => [
                    'service' => 'foo',
                    'method' => '__invoke'
                ]
            ]
        ];

        $this->assertEquals(['pointcuts' => [
            'services' => [
                'foo' => [
                    '__invoke' => [
                        'advices' => [
                            'after' => [
                                200 => [
                                    'baz'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'classes' => [
                'qux' => [
                    '__invoke' => [
                        'explicit' => true,
                        'advices' => [
                            'after' => [
                                100 => [
                                    'baz'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]], $subject->compile($services));
    }
}
