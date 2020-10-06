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
class RouteTest extends HttpTestCase
{
    public function testIndex()
    {
        $response = $this->client()->get('/');
        $this->assertSame(200, $response->getStatusCode());
    }
}
