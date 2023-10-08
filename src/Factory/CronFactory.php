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

use Hyperf\Contract\ContainerInterface;

class CronFactory
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function execute($name)
    {
        $callback = $this->container->get($name);
        $callback();
    }
}
