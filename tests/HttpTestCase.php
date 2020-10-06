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
namespace HyperfTest\Nano;

use GuzzleHttp\Client;
use Hyperf\Guzzle\CoroutineHandler;
use PHPStan\Testing\TestCase;

/**
 * @internal
 * @coversNothing
 */
class HttpTestCase extends TestCase
{
    public function client()
    {
        return new Client([
            'base_uri' => 'http://127.0.0.1:9501',
            'handler' => new CoroutineHandler(),
        ]);
    }
}
