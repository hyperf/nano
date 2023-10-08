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

namespace HyperfTest\Nano\Cases\Http;

use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionSource;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Nano\ContainerProxy;
use HyperfTest\Nano\HttpTestCase;

/**
 * @internal
 * @coversNothing
 */
class ContainerTest extends HttpTestCase
{
    public function testDi()
    {
        $response = $this->client()->get('/di');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('bar', $response->getBody()->getContents());
    }

    public function testDependencies()
    {
        $response = $this->client()->get('/foo');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('bar', $response->getBody()->getContents());
    }

    public function testPsrContainer()
    {
        $class = new class() extends ContainerProxy {
            public function __construct()
            {
                $container = new Container(new DefinitionSource([]));
                $container->set(RequestInterface::class, null);
                $container->set(ResponseInterface::class, null);
                parent::__construct($container);
            }
        };

        $class->set('id', $id = uniqid());
        $this->assertSame($id, $class->get('id'));
    }
}
