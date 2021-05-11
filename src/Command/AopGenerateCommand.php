<?php

/**
 * Copyright (C) GrizzIT, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace Ulrack\AopExtension\Command;

use GrizzIt\Task\Component\TaskList;
use GrizzIt\Task\Component\Task\ConfigurableTask;
use Ulrack\AopExtension\Common\CombinerInterface;
use GrizzIt\Command\Common\Command\InputInterface;
use Ulrack\AopExtension\Common\GeneratorInterface;
use GrizzIt\Command\Common\Command\OutputInterface;
use GrizzIt\Configuration\Common\RegistryInterface;
use GrizzIt\Command\Common\Command\CommandInterface;
use GrizzIt\Services\Common\Compiler\ServiceCompilerInterface;

class AopGenerateCommand implements CommandInterface
{
    /**
     * Contains the proxy generator.
     *
     * @var GeneratorInterface
     */
    private GeneratorInterface $proxyGenerator;

    /**
     * Contains the configuration combiner.
     *
     * @var CombinerInterface
     */
    private CombinerInterface $combiner;

    /**
     * Contains the service compiler.
     *
     * @var ServiceCompilerInterface
     */
    private ServiceCompilerInterface $serviceCompiler;

    /**
     * Contains configuration registry.
     *
     * @var RegistryInterface
     */
    private RegistryInterface $configRegistry;

    /**
     * Constructor.
     *
     * @param GeneratorInterface $proxyGenerator
     * @param CombinerInterface $combiner
     * @param ServiceCompilerInterface $serviceCompiler
     */
    public function __construct(
        GeneratorInterface $proxyGenerator,
        CombinerInterface $combiner,
        ServiceCompilerInterface $serviceCompiler,
        RegistryInterface $configRegistry
    ) {
        $this->proxyGenerator = $proxyGenerator;
        $this->combiner = $combiner;
        $this->serviceCompiler = $serviceCompiler;
        $this->configRegistry = $configRegistry;
    }

    /**
     * Executes the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    public function __invoke(
        InputInterface $input,
        OutputInterface $output
    ): void {
        $registry = $this->serviceCompiler->compile();
        $taskList = new TaskList();
        $combiner = $this->combiner;
        $combiner->setConfiguration(
            [
                'services' => $registry->getDefinitionByKey(
                    'pointcuts.services'
                ),
                'classes' => $registry->getDefinitionByKey(
                    'pointcuts.classes'
                )
            ]
        );

        $configKeys = array_keys(
            array_merge(
                ...array_column(
                    $this->configRegistry->toArray()['services'],
                    'services'
                )
            )
        );

        $proxyGenerator = $this->proxyGenerator;
        foreach ($configKeys as $service) {
            $configuration = $registry->getDefinitionByKey('services.' . $service);
            $task = new ConfigurableTask((function () use (
                $service,
                $configuration,
                $combiner,
                $proxyGenerator
            ) {
                $combined = $combiner->__invoke(
                    'services.' . $service,
                    $configuration['class']
                );

                if (count($combined) > 0) {
                    $proxyGenerator->generate($configuration['class']);
                }

                return true;
            }));
            $taskList->addTask($task, $service);
        }

        $output->outputProgressBar($taskList);
    }
}
