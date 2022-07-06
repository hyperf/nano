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

use Hyperf\Command\Command;
use Psr\Container\ContainerInterface;

class ClosureCommand extends Command
{
    protected ParameterParser $parameterParser;

    public function __construct(protected ContainerInterface $container, protected ?string $signature, private \Closure $closure)
    {
        $this->parameterParser = $container->get(ParameterParser::class);
        parent::__construct();
    }

    public function handle()
    {
        $inputs = array_merge($this->input->getArguments(), $this->input->getOptions());
        $parameters = $this->parameterParser->parseClosureParameters($this->closure, $inputs);

        call($this->closure->bindTo($this, $this), $parameters);
    }
}
