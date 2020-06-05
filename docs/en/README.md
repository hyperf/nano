# Nano

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

$app->addCommand('echo', function(){
    $this->get(StdoutLoggerInterface::class)->info('A new command called echo!');
});

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
        $this->get(StdoutLoggerInterface::class)->info('Processing...');
    }
});

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
