<?php

/**
 * Copyright (C) GrizzIT, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace Ulrack\AopExtension\Component\Compiler\Extension;

use Ulrack\Services\Common\AbstractServiceCompilerExtension;

class AopCompiler extends AbstractServiceCompilerExtension
{
    /**
     * Compile the services.
     *
     * @param array $services
     *
     * @return array
     */
    public function compile(array $services): array
    {
        $services = $this->preCompile(
            $services,
            $this->getParameters()
        )['services'];

        $inputServices = $services;
        $services['pointcuts'] = $this->compileConfiguration(
            $services['advices'] ?? [],
            $services['join-points'] ?? [],
            $services['pointcuts'] ?? []
        );

        unset($services['advices']);
        unset($services['join-points']);

        return $this->postCompile(
            $inputServices,
            $services,
            $this->getParameters()
        )['return'];
    }

    /**
     * Sorts all advices.
     *
     * @param array $joinPoints
     *
     * @return array
     */
    private function sortAdvices(array $joinPoints): array
    {
        foreach ($joinPoints as &$joinPoint) {
            foreach ($joinPoint['advices'] ?? [] as &$advices) {
                ksort($advices);
            }
        }

        return $joinPoints;
    }

    /**
     * Compiles the configuration and retrieves it.
     *
     * @param array $advices,
     * @param array $joinPoints,
     * @param array $pointcuts
     *
     * @return array
     */
    private function compileConfiguration(
        array $advices,
        array $joinPoints,
        array $pointcuts
    ): array {
        $configuration = [
            'services' => [],
            'classes' => []
        ];

        foreach ($pointcuts as $pointcut) {
            if (
                isset($joinPoints[$pointcut['join-point']]) &&
                isset($advices[$pointcut['advice']])
            ) {
                $joinPoints[$pointcut['join-point']]['advices']
                [$advices[$pointcut['advice']]['hook']][
                    $pointcut['sortOrder'] ?? 1000
                ][] = $advices[
                    $pointcut['advice']
                ]['service'];
            }
        }

        $joinPoints = $this->sortAdvices($joinPoints);
        foreach ($joinPoints as $joinPoint) {
            if (isset($joinPoint['class'])) {
                $configuration['classes'][ltrim($joinPoint['class'], '\\')]
                [$joinPoint['method']] = [
                    'explicit' => $joinPoint['explicit'] ?? false,
                    'advices' => $joinPoint['advices']
                ];

                continue;
            }

            $configuration['services'][$joinPoint['service']]
            [$joinPoint['method']] = [
                'advices' => $joinPoint['advices']
            ];
        }

        return $configuration;
    }
}
