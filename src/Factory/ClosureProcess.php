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

use Hyperf\Process\AbstractProcess;
use Psr\Container\ContainerInterface;

class ClosureProcess extends AbstractProcess
{
    /**
     * @var bool|callable
     */
    private $enable;

    public function __construct(ContainerInterface $container, private \Closure $closure)
    {
        parent::__construct($container);
    }

    public function handle(): void
    {
        call($this->closure->bindTo($this, $this));
    }

    public function isEnable($server): bool
    {
        if (is_callable($this->enable)) {
            return (bool) call($this->enable, []);
        }

        return (bool) $this->enable;
    }

    /**
     * @param bool|callable $enable
     */
    public function setEnable($enable): self
    {
        $this->enable = $enable;
        return $this;
    }

    public function setEnableCoroutine(bool $enableCoroutine): self
    {
        $this->enableCoroutine = $enableCoroutine;
        return $this;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setNums(int $nums): self
    {
        $this->nums = $nums;
        return $this;
    }

    public function setPipeType(int $pipeType): self
    {
        $this->pipeType = $pipeType;
        return $this;
    }

    public function setRedirectStdinStdout(bool $redirectStdinStdout): self
    {
        $this->redirectStdinStdout = $redirectStdinStdout;
        return $this;
    }

    public function setRestartInterval(int $restartInterval): self
    {
        $this->restartInterval = $restartInterval;
        return $this;
    }
}
