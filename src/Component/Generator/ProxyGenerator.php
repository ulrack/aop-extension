<?php

/**
 * Copyright (C) GrizzIT, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace Ulrack\AopExtension\Component\Generator;

use ReflectionClass;
use GrizzIt\Ast\Component\File\PhpFile;
use GrizzIt\Vfs\Common\FileSystemInterface;
use Ulrack\AopExtension\Common\GeneratorInterface;
use Ulrack\AopExtension\Component\Proxy\ProxyTrait;
use GrizzIt\Ast\Component\FileComponent\Php\Method\Method;
use GrizzIt\PhpAstGenerator\Factory\ClassGeneratorFactory;
use Ulrack\Kernel\Common\Manager\ResourceManagerInterface;
use GrizzIt\Ast\Component\FileComponent\Php\Variable\Variable;
use GrizzIt\PhpAstGenerator\Common\DefinitionGeneratorInterface;
use GrizzIt\Ast\Component\FileComponent\Php\Reference\UseReference;
use GrizzIt\Ast\Component\FileComponent\Php\Definition\ClassDefinition;

class ProxyGenerator implements GeneratorInterface
{
    /**
     * The directory which contains the generated classes.
     */
    private const GENERATED_DIRECTORY = 'generated';

    /**
     * Contains the resource manager.
     *
     * @var ResourceManagerInterface
     */
    private $resourceManager;

    /**
     * Contains the class generator factory.
     *
     * @var ClassGeneratorFactory.
     */
    private $classGeneratorFactory;

    /**
     * Contains the class generator
     *
     * @var DefinitionGeneratorInterface
     */
    private $classGenerator;

    /**
     * Contains the generated directory.
     *
     * @var FileSystemInterface
     */
    private $generatedDirectory;

    /**
     * Constructor.
     *
     * @param ResourceManagerInterface $resourceManager
     * @param ClassGeneratorFactory $classGeneratorFactory
     */
    public function __construct(
        ResourceManagerInterface $resourceManager,
        ClassGeneratorFactory $classGeneratorFactory
    ) {
        $this->resourceManager = $resourceManager;
        $this->classGeneratorFactory = $classGeneratorFactory;
    }

    /**
     * Retrieves the generated directory.
     *
     * @return FileSystemInterface
     */
    public function getGeneratedDirectory(): FileSystemInterface
    {
        if (is_null($this->generatedDirectory)) {
            $varFileSystem = $this->resourceManager->getVarFileSystem();
            if (!$varFileSystem->isDirectory(self::GENERATED_DIRECTORY)) {
                $varFileSystem->makeDirectory(self::GENERATED_DIRECTORY);
            }

            $this->generatedDirectory = $this->resourceManager
                ->getFileSystemDriver()
                ->connect(
                    $varFileSystem->realpath(self::GENERATED_DIRECTORY)
                );
        }

        return $this->generatedDirectory;
    }

    /**
     * Checks whether the class exists in the generated directory.
     *
     * @param string $class
     *
     * @return bool
     */
    private function isGenerated(string $class): bool
    {
        return $this->getGeneratedDirectory()
            ->isFile(str_replace('\\', '/', $class) . '.php');
    }

    /**
     * Retrieves the class generator.
     *
     * @return DefinitionGeneratorInterface
     */
    private function getClassGenerator(): DefinitionGeneratorInterface
    {
        if (is_null($this->classGenerator)) {
            $this->classGenerator = $this->classGeneratorFactory->create(
                false,
                false,
                false,
                true,
                false,
                false,
                true,
                false
            );
        }

        return $this->classGenerator;
    }

    /**
     * Generates a proxy class.
     *
     * @param string $className
     *
     * @return void
     */
    private function generateClass(string $className): void
    {
        /** @var ClassDefinition $baseClass */
        $baseClass = $this->getClassGenerator()->generate(
            new ReflectionClass($className)
        );

        $proxyName = explode('\\', $className . 'Proxy');
        $proxyName = array_pop($proxyName);
        $baseClass->setName($proxyName);
        $baseClass->setExtends('\\' . $className);
        $constructor = new Method('__construct', 'public');
        $constructor->setParameters(
            new Variable('subject', '\\' . $className),
            new Variable('pluginConfiguration', 'array')
        );

        $constructor->setMethodContent(
            '$this->subject = $subject;' . PHP_EOL .
            '$this->pluginConfiguration = $pluginConfiguration;'
        );

        $newMethods = [$constructor];
        foreach ($baseClass->getMethods() as $method) {
            if ($method->getName() !== '__construct') {
                $methodContent = ($method->getType() === 'void' ? '' : 'return ') .
                '$this->runPlugins(\'' .
                    $method->getName() . '\', [' . PHP_EOL;
                foreach ($method->getParameters() as $parameter) {
                    $methodContent .= '    \'' . $parameter->getName() .
                        '\' => $' . $parameter->getName() . ',' . PHP_EOL;
                }

                $methodContent .= ']);';

                $method->setMethodContent($methodContent);
                $newMethods[] = $method;
            }
        }

        $baseClass->setMethods(...$newMethods);
        $baseClass->setTraits(new UseReference('\\' . ProxyTrait::class));
        $fileName = str_replace('\\', '/', $className) . 'Proxy.php';
        $file = new PhpFile($fileName);
        $file->addComponent($baseClass);
        $directory = $this->getGeneratedDirectory();
        $path = '';
        foreach (array_filter(explode('/', $fileName), 'strlen') as $part) {
            $path .= '/' . $part;
            if (strpos($path, '.php') !== false) {
                $directory->put($path, $file->getContent());
                continue;
            }

            if (!$directory->isDirectory($path)) {
                $directory->makeDirectory($path);
            }
        }
    }

    /**
     * Generates the proxy class and returns the name.
     *
     * @param string $className
     *
     * @return string
     */
    public function generate(string $className): string
    {
        $className = ltrim($className, '\\');
        $proxyName = $className . 'Proxy';
        if (!$this->isGenerated($proxyName)) {
            $this->generateClass($className);
        }

        include_once(
            $this->getGeneratedDirectory()
                ->realpath(str_replace('\\', '/', $proxyName) . '.php')
        );

        return $proxyName;
    }
}
