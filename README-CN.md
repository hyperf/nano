[English](./README.md) | 中文

<p align="left">
  <a href="https://opencollective.com/hyperf"><img src="https://opencollective.com/hyperf/all/badge.svg?label=financial+contributors" alt="Financial Contributors on Open Collective"></a>
  <a href="https://secure.php.net/"><img src="https://img.shields.io/badge/php-%3E=7.2-brightgreen.svg?maxAge=2592000" alt="Php Version"></a>
  <a href="https://github.com/swoole/swoole-src"><img src="https://img.shields.io/badge/swoole-%3E=4.4-brightgreen.svg?maxAge=2592000" alt="Swoole Version"></a>
  <a href="https://github.com/hyperf/nano/blob/master/LICENSE"><img src="https://img.shields.io/github/license/hyperf/nano.svg?maxAge=2592000" alt="Nano License"></a>
</p>

# Nano, by Hyperf

Nano 是一款零配置、无骨架、极小化的 Hyperf 发行版，通过 Nano 可以让您仅仅通过 1 个 PHP 文件即可快速搭建一个 Hyperf 应用。   

## 设计理念

`Svelte` 的作者提出过一个论断：“框架不是用来组织代码的，是用来组织思路的”。而 Nano 最突出的一个优点就是不打断你的思路。Nano 非常擅长于自我声明，几乎不需要了解框架细节，只需要简单读一读代码，就能知道代码的目的。通过极简的代码声明，完成一个完整的 Hyperf 应用。

## 特性

* 无骨架
* 零配置
* 快速启动
* 闭包风格
* 支持注解外的全部 Hyperf 功能
* 兼容全部 Hyperf 组件
* Phar 友好

## 安装

```bash
composer require hyperf/nano
```

## 快速开始

创建一个 PHP 文件，如 index.php 如下：

```php
<?php
use Hyperf\Nano\Factory\AppFactory;

require_once __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();

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

启动服务：

```bash
php index.php start
```

简洁如此。

## 更多示例

### 路由

`$app` 集成了 Hyperf 路由器的所有方法。

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

### DI 容器
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
> 所有 $app 管理的闭包回调中，$this 都被绑定到了 `Hyperf\Nano\ContainerProxy` 上。

### 中间件
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

> 除了闭包之外，所有 $app->addXXX() 方法还接受类名作为参数。可以传入对应的 Hyperf 类。

### 异常处理

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

### 命令行

```php
<?php
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Nano\Factory\AppFactory;

require_once __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();

$app->addCommand('echo', function(){
    $this->get(StdoutLoggerInterface::class)->info('A new command called echo!');
});

$app->run();
```

执行

```bash
php index.php echo
```

### 事件监听

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

### 自定义进程

```php
<?php
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Nano\Factory\AppFactory;

require_once __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();

$app->addProcess(function(){
    while (true) {
        sleep(1);
        $this->get(StdoutLoggerInterface::class)->info('Processing...');
    }
});

$app->run();
```

### 定时任务

```php
<?php
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Nano\Factory\AppFactory;

require_once __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();

$app->addCrontab('* * * * * *', function(){
    $this->get(StdoutLoggerInterface::class)->info('execute every second!');
});

$app->run();
```

### AMQP

```php
<?php

use Hyperf\Nano\Factory\AppFactory;
use Hyperf\Amqp;

require_once __DIR__ . '/vendor/autoload.php';

class Message extends Amqp\Message\ProducerMessage
{
    protected $exchange = 'hyperf';

    protected $routingKey = 'hyperf';

    public function __construct($data)
    {
        $this->payload = $data;
    }
}

$app = AppFactory::createBase();
$container = $app->getContainer();

$app->config([
    'amqp' => [
        'default' => [
            'host' => 'localhost',
            'port' => 5672,
            'user' => 'guest',
            'password' => 'guest',
            'vhost' => '/',
            'concurrent' => [
                'limit' => 1,
            ],
            'pool' => [
                'min_connections' => 1,
                'max_connections' => 10,
                'connect_timeout' => 10.0,
                'wait_timeout' => 3.0,
                'heartbeat' => -1,
            ],
            'params' => [
                'insist' => false,
                'login_method' => 'AMQPLAIN',
                'login_response' => null,
                'locale' => 'en_US',
                'connection_timeout' => 3.0,
                'read_write_timeout' => 6.0,
                'context' => null,
                'keepalive' => false,
                'heartbeat' => 3,
                'close_on_destruct' => true,
            ],
        ],
    ],
]);

$app->addProcess(function () {
    $message = new class extends Amqp\Message\ConsumerMessage {
        protected $exchange = 'hyperf';

        protected $queue = 'hyperf';

        protected $routingKey = 'hyperf';

        public function consumeMessage($data, \PhpAmqpLib\Message\AMQPMessage $message): string
        {
            var_dump($data);
            return Amqp\Result::ACK;
        }
    };
    $consumer = $this->get(Amqp\Consumer::class);
    $consumer->consume($message);
});

$app->get('/', function () {
    /** @var Amqp\Producer $producer */
    $producer = $this->get(Amqp\Producer::class);
    $producer->produce(new Message(['id' => $id = uniqid()]));
    return $this->response->json([
        'id' => $id,
        'message' => 'Hello World.'
    ]);
});

$app->run();

```

### 使用更多 Hyperf 组件

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
