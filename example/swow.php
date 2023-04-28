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
    $user = $this->request->input('user', 'nano');
    $method = $this->request->getMethod();
    return [
        'message' => "hello {$user}",
        'method' => $method,
    ];
});

$app->addGroup('/route', function () use ($app) {
    $app->addRoute(['GET', 'POST'], '/{id:\d+}', function ($id) {
        return '/route/' . $id;
    });
    $app->put('/{name:.+}', function ($name) {
        return '/route/' . $name;
    });
});

$app->get('/middleware', function () {
    return $this->request->getAttribute('key');
});

$app->addMiddleware(function ($request, $handler) {
    $request = $request->withAttribute('key', 'value');
    return $handler->handle($request);
});

$app->run();
