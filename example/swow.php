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
use Hyperf\Nano\Factory\AppFactory;

require_once __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::createSwow();

$app->get('/', function () {
    return 'Hello World';
});

$app->run();
