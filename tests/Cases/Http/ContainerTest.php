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
}
