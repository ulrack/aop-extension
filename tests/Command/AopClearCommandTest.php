<?php

/**
 * Copyright (C) GrizzIT, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace Ulrack\AopExtension\Tests\Command;

use PHPUnit\Framework\TestCase;
use GrizzIt\Vfs\Common\FileSystemInterface;
use Ulrack\AopExtension\Command\AopClearCommand;
use Ulrack\Command\Common\Command\InputInterface;
use Ulrack\Command\Common\Command\OutputInterface;

/**
 * @coversDefaultClass \Ulrack\AopExtension\Command\AopClearCommand
 */
class AopClearCommandTest extends TestCase
{
    /**
     * @covers ::__invoke
     * @covers ::__construct
     * @covers ::removeRecursive
     *
     * @return void
     */
    public function testInvoke(): void
    {
        $generatedDirectory = $this->createMock(FileSystemInterface::class);
        $subject = new AopClearCommand($generatedDirectory);

        $generatedDirectory->expects(static::exactly(2))
            ->method('list')
            ->withConsecutive(['/'], ['/foo'])
            ->willReturnOnConsecutiveCalls(['foo', 'bar.json'], ['baz.json']);

        $generatedDirectory->expects(static::exactly(3))
            ->method('isFile')
            ->willReturn(true);

        $generatedDirectory->expects(static::exactly(3))
            ->method('isDirectory')
            ->willReturnOnConsecutiveCalls(true, false, false);

        $generatedDirectory->expects(static::exactly(2))
            ->method('unlink')
            ->withConsecutive(['/foo/baz.json'], ['/bar.json']);

        $generatedDirectory->expects(static::once())
            ->method('removeDirectory')
            ->with('/foo');

        $subject->__invoke(
            $this->createMock(InputInterface::class),
            $this->createMock(OutputInterface::class)
        );
    }
}
