English | [中文](./README-CN.md)

<p align="left">
  <a href="https://travis-ci.com/hyperf/nano"><img src="https://travis-ci.com/hyperf/nano.svg?branch=master"></a>
  <a href="https://opencollective.com/hyperf"><img src="https://opencollective.com/hyperf/all/badge.svg?label=financial+contributors" alt="Financial Contributors on Open Collective"></a>
  <a href="https://secure.php.net/"><img src="https://img.shields.io/badge/php-%3E=7.2-brightgreen.svg?maxAge=2592000" alt="Php Version"></a>
  <a href="https://github.com/swoole/swoole-src"><img src="https://img.shields.io/badge/swoole-%3E=4.4-brightgreen.svg?maxAge=2592000" alt="Swoole Version"></a>
  <a href="https://github.com/hyperf/nano/blob/master/LICENSE"><img src="https://img.shields.io/github/license/hyperf/nano.svg?maxAge=2592000" alt="Nano License"></a>
</p>

# Nano, by Hyperf

Nano is a zero-config, no skeleton, minimal Hyperf distribution that allows you to quickly build a Hyperf application with just a single PHP file.

## Purpose

The author of `Svelte` has said that "Frameworks are not tools for organizing your code, they are tools for organizing your mind". The biggest advantages of Nano is that it doesn't interrupt your mind of thought. The Nano is good at self-declaration that you have no need to know the details of the framework. You can read the code fastly and know what it's for. Write a complete Hyperf application with minimal code declarations.


## Feature

* No skeleton.
* Fast startup.
* Zero config.
* Closure style.
* Support all Hyperf features except annotations.
* Compatible with all Hyperf components.

## Example

Create a single PHP file, like `index.php`: 

```php
<?php
use Hyperf\Nano\Factory\AppFactory;

require_once __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create('0.0.0.0', 9051);

$app->get('/', function () {

    $user = $this->request->input('user', 'nano');
    $method = $this->request->getMethod();

    return [
        'message' => "hello {$user}",
        'method' => $method,
    ];

});

$app->run();
```

Run the server:

```bash
php index.php start
```

That's all you need.

## More Examples

### Routing

`$app` inherits all methods from hyperf router.

```php
<?php
use Hyperf\Nano\Factory\AppFactory;

require_once __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();

$app->addGroup('/nano', function () use ($app) {
    $app->addRoute(['GET', 'POST'], '/{id:\d+}', function($id) {
        return '/nano/'.$id;
    });
    $app->put('/{name:.+}', function($name) {
        return '/nano/'.$name;
    });
});

$app->run();
```

### DI Container 
```php
<?php
use Hyperf\Nano\ContainerProxy;
use Hyperf\Nano\Factory\AppFactory;

require_once __DIR__ . '/vendor/autoload.php';

class Foo {
    public function bar() {
        return 'bar';
    }   
}

$app = AppFactory::create();
$app->getContainer()->set(Foo::class, new Foo());

$app->get('/', function () {
    /** @var ContainerProxy $this */
    $foo = $this->get(Foo::class);
    return $foo->bar();
});

$app->run();
```
> As a convention, $this is bind to ContainerProxy in all closures managed by nano, including middleware, exception handler and more.

### Middleware
```php
<?php
use Hyperf\Nano\Factory\AppFactory;

require_once __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();

$app->get('/', function () {
    return $this->request->getAttribute('key');
});

$app->addMiddleware(function ($request, $handler) {
    $request = $request->withAttribute('key', 'value');
    return $handler->handle($request);
});

$app->run();
```

> In addition to closure, all $app->addXXX() methods also accept class name as argument. You can pass any corresponding hyperf classes.

### ExceptionHandler

```php
<?php
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Nano\Factory\AppFactory;

require_once __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();

$app->get('/', function () {
    throw new \Exception();
});

$app->addExceptionHandler(function ($throwable, $response) {
    return $response->withStatus('418')
        ->withBody(new SwooleStream('I\'m a teapot'));
});

$app->run();
```

### Custom Command

```php
<?php
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Nano\Factory\AppFactory;

require_once __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();

$app->addCommand('echo {--name=Nano}', function($name){
    $this->output->info("Hello, {$name}");
})->setDescription('The echo command.');

$app->run();
```

To run this command, execute
```bash
php index.php echo
```

### Event Listener
```php
<?php
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Framework\Event\BootApplication;
use Hyperf\Nano\Factory\AppFactory;

require_once __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();

$app->addListener(BootApplication::class, function($event){
    $this->get(StdoutLoggerInterface::class)->info('App started');
});

$app->run();
```

### Custom Process
```php
<?php
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Nano\Factory\AppFactory;

require_once __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();

$app->addProcess(function(){
    while (true) {
        sleep(1);
        $this->container->get(StdoutLoggerInterface::class)->info('Processing...');
    }
})->setName('nano-process')->setNums(1);

$app->addProcess(function(){
    $this->container->get(StdoutLoggerInterface::class)->info('Determine whether the process needs to be started based on env...');
})->setName('nano-process')->setNums(1)->setEnable(\Hyperf\Support\env('PROCESS_ENABLE', true))));

$app->run();
```

### Crontab

```php
<?php
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Nano\Factory\AppFactory;

require_once __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();

$app->addCrontab('* * * * * *', function(){
    $this->get(StdoutLoggerInterface::class)->info('execute every second!');
})->setName('nano-crontab')->setOnOneServer(true)->setMemo('Test crontab.');

$app->run();
```

### Use Hyperf Component

```php
<?php
use Hyperf\DB\DB;
use Hyperf\Nano\Factory\AppFactory;

require_once __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();

$app->config([
    'db.default' => [
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', 3306),
        'database' => env('DB_DATABASE', 'hyperf'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
    ]
]);

$app->get('/', function(){
    return DB::query('SELECT * FROM `user` WHERE gender = ?;', [1]);
});

$app->run();
```

### How to use Swow

- require swow engine

```shell
composer require "hyperf/engine-swow:^2.0"
```

- run the code

```php
<?php

declare(strict_types=1);

use Hyperf\Nano\Factory\AppFactory;

require_once __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::createSwow();

$app->get('/', function () {
    return 'Hello World';
});

$app->run();
```
