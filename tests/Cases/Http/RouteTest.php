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

use GuzzleHttp\RequestOptions;
use Hyperf\Utils\Codec\Json;
use HyperfTest\Nano\HttpTestCase;

/**
 * @internal
 * @coversNothing
 */
class RouteTest extends HttpTestCase
{
    public function testStaticRoute()
    {
        $response = $this->client()->get('/');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['message' => 'hello nano', 'method' => 'GET'], Json::decode($response->getBody()->getContents()));

        $response = $this->client()->get('/', [
            RequestOptions::QUERY => [
                'user' => 'hyperf',
            ],
        ]);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['message' => 'hello hyperf', 'method' => 'GET'], Json::decode($response->getBody()->getContents()));
    }

    public function testDynamicRoute()
    {
        $response = $this->client()->get($route = '/route/' . rand(1000, 9999));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($route, $response->getBody()->getContents());
    }

    public function testMiddleware()
    {
        $response = $this->client()->get('/middleware');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('value', $response->getBody()->getContents());
    }
}
