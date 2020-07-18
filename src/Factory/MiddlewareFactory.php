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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareFactory
{
    public function create(\Closure $closure): MiddlewareInterface
    {
        return new class($closure) implements MiddlewareInterface {
            /**
             * @var \Closure
             */
            private $closure;

            public function __construct(\Closure $closure)
            {
                $this->closure = $closure;
            }

            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return call($this->closure, [$request, $handler]);
            }
        };
    }
}
