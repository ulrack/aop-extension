<?php

/**
 * Copyright (C) GrizzIT, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace Ulrack\AopExtension\Command;

use GrizzIt\Task\Component\TaskList;
use GrizzIt\Task\Component\Task\ConfigurableTask;
use Ulrack\AopExtension\Common\CombinerInterface;
use Ulrack\Command\Common\Command\InputInterface;
use Ulrack\AopExtension\Common\GeneratorInterface;
use Ulrack\Command\Common\Command\OutputInterface;
use Ulrack\Command\Common\Command\CommandInterface;
use Ulrack\Services\Common\ServiceCompilerInterface;

class AopGenerateCommand implements CommandInterface
{
    /**
     * Contains the proxy generator.
     *
     * @var GeneratorInterface
     */
    private $proxyGenerator;

    /**
     * Contains the configuration combiner.
     *
     * @var CombinerInterface
     */
    private $combiner;

    /**
     * Contains the service compiler.
     *
     * @var ServiceCompilerInterface
     */
    private $serviceCompiler;

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
        ServiceCompilerInterface $serviceCompiler
    ) {
        $this->proxyGenerator = $proxyGenerator;
        $this->combiner = $combiner;
        $this->serviceCompiler = $serviceCompiler;
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
        $services = $this->serviceCompiler->compile();
        $taskList = new TaskList();
        $combiner = $this->combiner;
        $combiner->setConfiguration($services['pointcuts']);
        $proxyGenerator = $this->proxyGenerator;
        foreach ($services['services'] as $service => $configuration) {
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
