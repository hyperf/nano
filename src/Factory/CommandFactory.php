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

class CommandFactory
{
    public function create(string $name, \Closure $closure): Command
    {
        return new class($name, $closure) extends Command {
            public function __construct(string $name, private \Closure $closure)
            {
                parent::__construct($name);
            }

            public function handle()
            {
                call($this->closure);
            }
        };
    }
}
