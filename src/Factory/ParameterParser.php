<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf Nano.
 *
 * @link     https://www.hyperf.io
 * @document https://nano.hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/nano/blob/master/LICENSE
 */
namespace Hyperf\Nano\Factory;

use Closure;
use Hyperf\Contract\NormalizerInterface;
use Hyperf\Di\ClosureDefinitionCollectorInterface;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use Psr\Container\ContainerInterface;

class ParameterParser
{
    private ?ContainerInterface $container;

    private ?NormalizerInterface $normalizer;

    private ?ClosureDefinitionCollectorInterface $closureDefinitionCollector;

    private ?MethodDefinitionCollectorInterface $methodDefinitionCollector;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->normalizer = $this->container->get(NormalizerInterface::class);

        if ($this->container->has(ClosureDefinitionCollectorInterface::class)) {
            $this->closureDefinitionCollector = $this->container->get(ClosureDefinitionCollectorInterface::class);
        }

        if ($this->container->has(MethodDefinitionCollectorInterface::class)) {
            $this->methodDefinitionCollector = $this->container->get(MethodDefinitionCollectorInterface::class);
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function parseClosureParameters(Closure $closure, array $arguments): array
    {
        if (! $this->closureDefinitionCollector) {
            return [];
        }

        $definitions = $this->closureDefinitionCollector->getParameters($closure);

        return $this->getInjections($definitions, 'Closure', $arguments);
    }

    public function parseMethodParameters(string $class, string $method, array $arguments): array
    {
        $definitions = $this->methodDefinitionCollector->getParameters($class, $method);
        return $this->getInjections($definitions, "{$class}::{$method}", $arguments);
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function getInjections(array $definitions, string $callableName, array $arguments): array
    {
        $injections = [];

        foreach ($definitions as $pos => $definition) {
            $value = $arguments[$pos] ?? $arguments[$definition->getMeta('name')] ?? null;
            if ($value === null) {
                if ($definition->getMeta('defaultValueAvailable')) {
                    $injections[] = $definition->getMeta('defaultValue');
                } elseif ($this->container->has($definition->getName())) {
                    $injections[] = $this->container->get($definition->getName());
                } elseif ($definition->allowsNull()) {
                    $injections[] = null;
                } else {
                    throw new \InvalidArgumentException("Parameter '{$definition->getMeta('name')}' "
                        . "of {$callableName} should not be null");
                }
            } else {
                $injections[] = $this->normalizer->denormalize($value, $definition->getName());
            }
        }

        return $injections;
    }
}
