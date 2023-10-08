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
use Hyperf\Command\Command;
use Hyperf\Context\ApplicationContext;

class CommandFactory
{
    public function create(string $signature, Closure $closure): Command
    {
        $container = ApplicationContext::getContainer();

        return new ClosureCommand($container, $signature, $closure);
    }
}
