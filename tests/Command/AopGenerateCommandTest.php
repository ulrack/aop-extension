<?php

/**
 * Copyright (C) GrizzIT, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace Ulrack\AopExtension\Tests\Command;

use PHPUnit\Framework\TestCase;
use GrizzIt\Task\Common\TaskListInterface;
use Ulrack\AopExtension\Common\CombinerInterface;
use Ulrack\Command\Common\Command\InputInterface;
use Ulrack\AopExtension\Common\GeneratorInterface;
use Ulrack\Command\Common\Command\OutputInterface;
use Ulrack\AopExtension\Command\AopGenerateCommand;
use Ulrack\Services\Common\ServiceCompilerInterface;

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
        $subject = new AopGenerateCommand(
            $proxyGenerator,
            $combiner,
            $serviceCompiler
        );

        $output = $this->createMock(OutputInterface::class);
        $serviceCompiler->expects(static::once())
            ->method('compile')
            ->willReturn(
                [
                    'services' => [
                        'foo' => [
                            'class' => 'bar'
                        ]
                    ],
                    'pointcuts' => ['pointcut-config']
                ]
            );

        $combiner->expects(static::once())
            ->method('setConfiguration')
            ->with(['pointcut-config']);

        $output->expects(static::once())
            ->method('outputProgressBar')
            ->with($this->isInstanceOf(TaskListInterface::class))
            ->will($this->returnCallback(function (TaskListInterface $taskList) {
                $taskList->next();
            }));

        $combiner->expects(static::once())
            ->method('__invoke')
            ->with('services.foo', 'bar')
            ->willReturn(['foo']);

        $proxyGenerator->expects(static::once())
            ->method('generate')
            ->with('bar');

        $subject->__invoke(
            $this->createMock(InputInterface::class),
            $output
        );
    }
}
