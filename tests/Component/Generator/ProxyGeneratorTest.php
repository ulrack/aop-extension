<?php

/**
 * Copyright (C) GrizzIT, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace Ulrack\AopExtension\Tests\Component\Generator;

use PHPUnit\Framework\TestCase;
use GrizzIt\Ast\Common\Php\MethodInterface;
use GrizzIt\Vfs\Common\FileSystemInterface;
use GrizzIt\Ast\Common\Php\VariableInterface;
use Ulrack\AopExtension\Tests\Mock\TraitMock;
use GrizzIt\PhpAstGenerator\Factory\ClassGeneratorFactory;
use Ulrack\Kernel\Common\Manager\ResourceManagerInterface;
use Ulrack\AopExtension\Component\Generator\ProxyGenerator;
use GrizzIt\Ast\Component\FileComponent\Php\Definition\ClassDefinition;
use GrizzIt\PhpAstGenerator\Component\Generator\Definition\ClassGenerator;

/**
 * @coversDefaultClass \Ulrack\AopExtension\Component\Generator\ProxyGenerator
 */
class ProxyGeneratorTest extends TestCase
{
    /**
     * @covers ::generate
     * @covers ::getGeneratedDirectory
     * @covers ::isGenerated
     * @covers ::getClassGenerator
     * @covers ::generateClass
     * @covers ::__construct
     *
     * @return void
     */
    public function testGenerate(): void
    {
        $resourceManager = $this->createMock(ResourceManagerInterface::class);
        $generatorFactory = $this->createMock(ClassGeneratorFactory::class);
        $generator = $this->createMock(ClassGenerator::class);
        $baseClass = $this->createMock(ClassDefinition::class);
        $method = $this->createMock(MethodInterface::class);
        $generatorFactory->expects(static::once())
            ->method('create')
            ->willReturn($generator);

        $generator->expects(static::once())
            ->method('generate')
            ->willReturn($baseClass);

        $baseClass->expects(static::once())
            ->method('getMethods')
            ->willReturn([$method]);

        $method->expects(static::once())
            ->method('getParameters')
            ->willReturn([$this->createMock(VariableInterface::class)]);

        $subject = new ProxyGenerator($resourceManager, $generatorFactory);
        $fileSystem = $subject->getGeneratedDirectory();
        $this->assertInstanceOf(FileSystemInterface::class, $fileSystem);
        $fileSystem->expects(static::once())
            ->method('realpath')
            ->willReturn(realpath(__DIR__ . '/../../Mock/TraitMock.php'));

        $className = TraitMock::class;
        $this->assertEquals($className . 'Proxy', $subject->generate($className));
    }
}
