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
namespace Hyperf\Nano;

use Hyperf\Command\Command;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\DB\DB;
use Hyperf\Framework\Event\BootApplication;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Nano\Factory\AppFactory;

require_once __DIR__ . '/../vendor/autoload.php';

interface FooInterface
{
    public function bar(): string;
}

class Foo implements FooInterface
{
    public function bar(): string
    {
        return 'bar';
    }
}

$app = AppFactory::createBase('0.0.0.0', 9501, [
    FooInterface::class => Foo::class,
]);
$app->config([
    'server' => [
        'settings' => [
            'daemonize' => (int) env('DAEMONIZE', 0),
        ],
    ],
]);

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

$app->get('/di', function () {
    /** @var ContainerProxy $this */
    $foo = $this->get(Foo::class);
    return $foo->bar();
});

$app->get('/foo', function () {
    /* @var ContainerProxy $this */
    return $this->get(FooInterface::class)->bar();
});

$app->get('/middleware', function () {
    return $this->request->getAttribute('key');
});

$app->addMiddleware(function ($request, $handler) {
    $request = $request->withAttribute('key', 'value');
    return $handler->handle($request);
});

$app->get('/exception', function () {
    throw new \Exception();
});

$app->addExceptionHandler(function ($throwable, $response) {
    return $response->withStatus('418')->withBody(new SwooleStream('I\'m a teapot'));
});

$app->addCommand('echo {--name=Nano}', function ($name) {
    /* @var Command $this */
    $this->output->info("Hello, {$name}!");
})->setDescription('The echo command.');

$app->addListener(BootApplication::class, function ($event) {
    $this->get(StdoutLoggerInterface::class)->info('App started');
});

$app->addProcess(function () {
    $name = $this->name;
    while (true) {
        sleep(1);
        $this->container->get(StdoutLoggerInterface::class)->info("{$name} Processing...");
    }
})->setName('nano-process')->setEnable(fn ($server) => true);

$app->addCrontab('* * * * * *', function () {
    $this->get(StdoutLoggerInterface::class)->info('execute every second!');
})->setName('nano-crontab');

$app->config([
    'db.default' => [
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', 3306),
        'database' => env('DB_DATABASE', 'hyperf'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
    ],
]);

$app->get('/db', function () {
    return DB::query('SELECT * FROM `user` WHERE gender = ?;', [1]);
});

$app->run();
