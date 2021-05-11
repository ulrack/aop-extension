<?php

/**
 * Copyright (C) GrizzIT, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace Ulrack\AopExtension\Command;

use GrizzIt\Vfs\Common\FileSystemInterface;
use GrizzIt\Command\Common\Command\InputInterface;
use GrizzIt\Command\Common\Command\OutputInterface;
use GrizzIt\Command\Common\Command\CommandInterface;

class AopClearCommand implements CommandInterface
{
    /**
     * Contains the generated directory.
     *
     * @var FileSystemInterface
     */
    private FileSystemInterface $generatedDirectory;

    /**
     * Constructor.
     *
     * @param FileSystemInterface $generatedDirectory
     */
    public function __construct(FileSystemInterface $generatedDirectory)
    {
        $this->generatedDirectory = $generatedDirectory;
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
        $output->writeLine('Removing generated classes.');
        $this->removeRecursive('/', $output);
        $output->writeLine('Done.');
    }

    /**
     * Removes all directories and their contents.
     *
     * @param string $directory
     * @param OutputInterface $output
     *
     * @return void
     */
    private function removeRecursive(
        string $directory,
        OutputInterface $output
    ): void {
        foreach ($this->generatedDirectory->list($directory) as $file) {
            $file = rtrim($directory, '/') . '/' . $file;
            if (
                $this->generatedDirectory->isFile($file) &&
                !$this->generatedDirectory->isDirectory($file)
            ) {
                $output->writeLine(
                    sprintf(
                        'Removing file %s.',
                        $file
                    ),
                    'text',
                    true
                );

                $this->generatedDirectory->unlink($file);
                continue;
            }

            $this->removeRecursive($file, $output);

            $output->writeLine(
                sprintf(
                    'Removing directory %s.',
                    $file
                ),
                'text',
                true
            );
            $this->generatedDirectory->removeDirectory($file);
        }
    }
}
