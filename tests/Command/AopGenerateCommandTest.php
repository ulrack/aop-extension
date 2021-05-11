<?php

/**
 * Copyright (C) GrizzIT, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace Ulrack\AopExtension\Tests\Command;

use PHPUnit\Framework\TestCase;
use GrizzIt\Task\Common\TaskListInterface;
use Ulrack\AopExtension\Common\CombinerInterface;
use GrizzIt\Command\Common\Command\InputInterface;
use Ulrack\AopExtension\Common\GeneratorInterface;
use GrizzIt\Command\Common\Command\OutputInterface;
use GrizzIt\Configuration\Common\RegistryInterface;
use Ulrack\AopExtension\Command\AopGenerateCommand;
use GrizzIt\Services\Common\Compiler\ServiceCompilerInterface;
use GrizzIt\Services\Common\Registry\ServiceRegistryInterface;

/**
 * @coversDefaultClass \Ulrack\AopExtension\Command\AopGenerateCommand
 */
class AopGenerateCommandTest extends TestCase
{
    /**
     * @covers ::__invoke
     * @covers ::__construct
     *
     * @return void
     */
    public function testInvoke(): void
    {
        $proxyGenerator = $this->createMock(GeneratorInterface::class);
        $combiner = $this->createMock(CombinerInterface::class);
        $serviceCompiler = $this->createMock(ServiceCompilerInterface::class);
        $registry = $this->createMock(RegistryInterface::class);
        $serviceRegistry = $this->createMock(ServiceRegistryInterface::class);
        $subject = new AopGenerateCommand(
            $proxyGenerator,
            $combiner,
            $serviceCompiler,
            $registry
        );

        $output = $this->createMock(OutputInterface::class);
        $serviceCompiler->expects(static::once())
            ->method('compile')
            ->willReturn($serviceRegistry);

        $serviceRegistry->expects(static::exactly(3))
            ->method('getDefinitionByKey')
            ->withConsecutive(
                ['pointcuts.services'],
                ['pointcuts.classes'],
                ['services.foo']
            )->willReturnOnConsecutiveCalls(
                ['pointcut-service-config'],
                ['pointcut-class-config'],
                ['class' => 'foo']
            );

        $combiner->expects(static::once())
            ->method('setConfiguration')
            ->with([
                'services' => ['pointcut-service-config'],
                'classes' => ['pointcut-class-config']
            ]);

        $registry->expects(static::once())
            ->method('toArray')
            ->willReturn(
                [
                    'services' => [
                        [
                            'services' => [
                                'foo' => [
                                    'class' => 'foo'
                                ]
                            ]
                        ]
                    ]
                ]
            );

        $output->expects(static::once())
            ->method('outputProgressBar')
            ->with($this->isInstanceOf(TaskListInterface::class))
            ->will($this->returnCallback(function (TaskListInterface $taskList) {
                $taskList->next();
            }));

        $combiner->expects(static::once())
            ->method('__invoke')
            ->with('services.foo', 'foo')
            ->willReturn(['foo']);

        $proxyGenerator->expects(static::once())
            ->method('generate')
            ->with('foo');

        $subject->__invoke(
            $this->createMock(InputInterface::class),
            $output
        );
    }
}
